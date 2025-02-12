<?php $ldap = AVideoPlugin::getObjectData("AuthLDAP"); ?>

<div class="panel panel-default <?php echo getCSSAnimationClassAndStyle(); getCSSAnimationClassAndStyleAddWait(0.5); ?>">
  <div class="panel-heading">
      <h2 class="<?php echo getCSSAnimationClassAndStyle(); ?>">
          <?php echo($ldap->loginPageTitle); ?>
      </h2>
  </div>

  <div class="panel-body">
    <form id="ldapForm" class="form-horizontal">
      <div class="form-group <?php echo getCSSAnimationClassAndStyle(); ?>" >
        <label class="col-md-4 control-label"><?php echo __("User"); ?></label>
        <div class="col-md-8 inputGroupContainer">
          <div class="input-group">
            <span class="input-group-addon"><i class="glyphicon glyphicon-user"></i></span>
            <input  id="ldapuser" placeholder="<?php echo __("User"); ?>" class="form-control" type="text" value="" required>
          </div>
        </div>
      </div>
    
      <div class="form-group <?php echo getCSSAnimationClassAndStyle(); ?>">
        <label class="col-md-4 control-label"><?php echo __("Password"); ?></label>
        <div class="col-md-8 inputGroupContainer">
            <?php getInputPassword("ldappw"); ?>
        </div>
      </div>
    
      <div class="form-group" style="<?php echo User::isCaptchaNeed() ? "" : "display: none;" ?>" id="captchaFormAuthLdap">
        <?php echo User::getCaptchaForm("authldap"); ?>
      </div>
    
      <div class="form-group <?php echo getCSSAnimationClassAndStyle(); ?>">
        <div class="col-md-12">
          <button type="submit" class="btn btn-success  btn-block <?php echo getCSSAnimationClassAndStyle(); ?>" id="authLdapButton" >
            <span class="fas fa-sign-in-alt"></span> <?php echo __("Sign in"); ?>
          </button>
        </div>
      </div>
    </form>
  </div>
</div>

<script>
$(document).ready(function () {
    $('#ldapForm').submit(function (evt) {
        evt.preventDefault();
        modal.showPleaseWait();
        $.ajax({
            type: 'post',
            url: '<?php echo $global['webSiteRootURL']; ?>plugin/AuthLDAP/authldap_api.php',
            data: {
                "user": $('#ldapuser').val(), 
                "pass": $('#ldappw').val(), 
                "captcha": $('#captchaTextauthldap').val()
	    },
            success: function (response) {
  
                if (!response.Authenticated) {
                    modal.hidePleaseWait();

                    if (response.error) {
                        avideoAlert("<?php echo __("Sorry!"); ?>", response.error, "error");
                    } else {
                        avideoAlert("<?php echo __("Sorry!"); ?>", "<?php echo __("Your user or password is wrong!"); ?>", "error");
	            }

                    if (response.needCaptcha) {
                        $("#btnReloadCapcha").trigger('click');
                        $('#captchaFormAuthLdap').slideDown();
                    }

                } else {
                    document.location = '<?php echo $global['webSiteRootURL']; ?>'
                }
            }
        });
    });
});
</script>
