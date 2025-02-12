<?php
require_once $global['systemRootPath'] . 'plugin/Plugin.abstract.php';

class AuthLDAP extends PluginAbstract {
    /* Set plugin's name */
    public function getName() {
        return "AuthLDAP";
    }

    /* Set plugin's description */
    public function getDescription() {
        $desc = "Authentication LDAP or Active Directory";
        return $desc;
    }

    /* Set plugin's tag infomation */
    public function getTags() {
    $tags = array(
            PluginTags::$FREE,
            PluginTags::$LOGIN,
        );
        return $tags;
    }

    /* Set plugin's UUID */
    public function getUUID() {
        return "47f261ce-f2cd-11ec-b939-0242ac120002";
    }

    /* Set plugin's params and default settings. */
    public function getEmptyDataObject() {
        global $global;

        $conf = new stdClass();
        $conf->ldapUri = "ldap://localhost/";
        $conf->bindDn = 'cn=Manager';
        $conf->bindPw = '';
        $conf->baseDn = 'ou=User,dc=example,dc=com';
        $conf->searchFilter = '(uid=%s)';
        $conf->ldapProtocolVersion = 3;
        $conf->firstNameAttr = 'givenname';
        $conf->lastNameAttr = 'sn';
        $conf->emailAttr = 'mail';
        $conf->defaultProfilePhoto = $global['webSiteRootURL'] . 'view/img/userSilhouette.jpg';
        $conf->ifLdapLoginFailTryDatabase = false;
        $conf->loginPageTitle = "LDAP authentiation form";

        return $conf;
    }

    /* Set AuthLDAP plugin's login page. */
    public function getLogin() {
        global $global;
        return $global['systemRootPath'] . 'plugin/AuthLDAP/authldap_loginform.php';
    }

    /* Main login method. */
    static function login($user, $pass) {
        global $global;
        require_once $global['systemRootPath'] . 'objects/user.php';

        if(!User::checkLoginAttempts()){
            return User::CAPTCHA_ERROR;
        }

        /* 
         * Perform LDAP authentication, and if it fails,
         * switch to Avideo authentication.
         */
        $res = self::ldapAuth($user, $pass, $info);
        if ($res !== User::USER_LOGGED) {
            $res = self::localAuth($user, $pass, $info);
            if ($res === User::USER_LOGGED) {
                return $res;
            }
	    return User::USER_NOT_FOUND;
        }

        /* Create user if not exitst.  */
        User::createUserIfNotExists($user, 
                                    "",  // password, Specify an empty password to set a random password
                                    $info["lastName"]. " " . $info["firstName"],
                                    $info["mail"],
                                    $authldap->defaultProfilePhoto);
        $userObj = new User(0, $user, $pass);

        /* If the argument is true, it will be authenticated regardless of the user or password.*/
        $userObj->login(true);

        return User::USER_LOGGED;
    }

    /* LDAP authentication method */
    private static function ldapAuth($user, $pass, &$info) {
        $errobj = new stdClass();
    
        /* Validations */
        if (empty($user) || empty($pass)) {
            _error_log("LDAP auth failed: Empty user or password.");
            return User::USER_NOT_FOUND;
        }
    
        $al = AVideoPlugin::getObjectData("AuthLDAP"); 
        if (empty($al->ldapUri)) {
            $errobj->msg = "ldapUri setting is required";
            die(json_encode($errobj));
        }
    
        if (empty($al->bindDn)) {
            $errobj->msg = "ldap bindDn setting is required";
            die(json_encode($errobj));
        }
    
        if (empty($al->bindPw)) {
            $errobj->msg = "ldap bindPw setting is required";
            die(json_encode($errobj));
        }
    
        if (empty($al->baseDn)) {
            $errobj->msg = "ldap baseDn setting is required";
            die(json_encode($errobj));
        }
    
        if (empty($al->searchFilter)) {
            $errobj->msg = "ldap searchFilter setting is required";
            die(json_encode($errobj));
        }
    
    
        /* First, identify the user's DN with manager privileges */
        $ldap = @ldap_connect($al->ldapUri);
        if ($ldap === False) {
            _error_log("LDAP connection failed: " . ldap_errno($connect) . ": " . print_r(ldap_error($ldap), true));
            return User::USER_NOT_FOUND;
        }
    
        ldap_set_option($ldap, LDAP_OPT_PROTOCOL_VERSION, $al->ldapProtocolVersion);
        ldap_set_option($ldap, LDAP_OPT_NETWORK_TIMEOUT, 3);
        ldap_set_option($ldap, LDAP_OPT_TIMELIMIT, 3);
    
        $bind = @ldap_bind($ldap, $al->bindDn, $al->bindPw);
        if ($bind === False) {
            _error_log("LDAP bind failed: " . ldap_errno($ldap) . ": " . print_r(ldap_error($ldap), true));
            ldap_close($ldap);
            return User::USER_NOT_FOUND;
        }
    
	/* If the email format is incorrect, the email will be empty */
        $ldapattr = array($al->firstNameAttr, $al->lastNameAttr, $al->emailAttr);
    
        /* Replace user filter */
        $filter = sprintf($al->searchFilter, $user);
        $res = @ldap_search($ldap, $al->baseDn, $filter, $ldapattr);
        if ($res === False) {
            _error_log("LDAP search failed: " . ldap_errno($ldap) . ": " . print_r(ldap_error($ldap), true));
            ldap_close($ldap);
            return User::USER_NOT_FOUND;
        }
    
        /*
         * Get user entry.
         * If there are two or more entries, an error will occur. 
         */
        $entry = ldap_get_entries($ldap, $res);
	if ($entry === False || $entry["count"] === 0) {
            _error_log("LDAP user not found: ". $filter, true);
            ldap_close($ldap);
            return User::USER_NOT_FOUND;
        }

        if ($entry["count"] > 1) {
            _error_log("LDAP search multiple result", true);
            ldap_close($ldap);
            return User::USER_NOT_FOUND;
        }
        ldap_close($ldap);
    
        # User self Bind
        $userdn = $entry[0]["dn"];
    
        $ldap = @ldap_connect($al->ldapUri);
        if ($ldap === False) {
            _error_log("LDAP connection failed: " . ldap_errno($connect) . ": " . print_r(ldap_error($ldap), true));
            return User::USER_NOT_FOUND;
        }
    
        ldap_set_option($ldap, LDAP_OPT_PROTOCOL_VERSION, $al->ldapProtocolVersion);
        ldap_set_option($ldap, LDAP_OPT_NETWORK_TIMEOUT, 3);
        ldap_set_option($ldap, LDAP_OPT_TIMELIMIT, 3);
    
        $bind = @ldap_bind($ldap, $userdn, $pass);
        if ($bind === False) {
            _error_log("LDAP self bind failed: " . ldap_errno($ldap) . ": " . print_r(ldap_error($ldap), true));
            ldap_close($ldap);
            return User::USER_NOT_FOUND;
        }
        ldap_close($ldap);
    

        /* Returns user attributes by reference */
        $info["mail"] = "";
        if (isset($entry[0][$al->emailAttr][0]) && !empty($entry[0][$al->emailAttr][0])) {
            $info["mail"] = $entry[0][$al->emailAttr][0];
        } 
    
        $info["firstName"] = "";
        if (isset($entry[0][$al->firstNameAttr][0]) && !empty($entry[0][$al->firstNameAttr][0])) {
            $info["firstName"] = $entry[0][$al->firstNameAttr][0];
        } 
    
        $info["lastName"] = "";
        if (isset($entry[0][$al->lastNameAttr][0]) && !empty($entry[0][$al->lastNameAttr][0])) {
            $info["lastName"] = $entry[0][$al->lastNameAttr][0];
        } 
    
        return User::USER_LOGGED;
    }

    /* Avideo local auth */
    private static function localAuth($user, $pass, &$info) {
        $al = AVideoPlugin::getObjectData("AuthLDAP");
        if($al->ifLdapLoginFailTryDatabase) {
            $info = array("mail"=>"", "firstName"=>"", "lastName"=>"");
            $userObj = new User(0, $user, $pass);
            return $userObj->login();
        }

        return User::USER_NOT_FOUND;
    }
}
