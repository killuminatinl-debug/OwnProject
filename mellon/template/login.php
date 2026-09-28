<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <title>Mellon</title>
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <link href="<?php echo $base; ?>/mellon/min?g=core-styles" media="screen" rel="stylesheet" type="text/css">
        <link href="<?php echo $base; ?>/mellon/min?g=styles" media="screen" rel="stylesheet" type="text/css">

        <script type="text/javascript" src="<?php echo $base; ?>/mellon/min?g=core-scripts"></script>
        <script type="text/javascript" src="<?php echo $base; ?>/mellon/min?g=scripts"></script>
        <script type="text/javascript">
            //<!--
            $(function () {
                if (!window.parent || !window.parent.bridge) {
                    return;
                }
                var bridge = window.parent.bridge;

                var body = $('body');
                setTimeout(function () {
                    bridge.resize(body.width(), body.height());
                    setTimeout(function () {
                        bridge.resize(body.width(), body.height());
                    }, 400);
                }, 30);

            });
            //-->
        </script>
        <script type="text/javascript">
            //<!--
            $(function () {
                if (!window.parent || !window.parent.bridge) {
                    return;
                }
                var bridge = window.parent.bridge;
                bridge.setCookie("msid", "<?php echo $_SESSION['mellon_msid']; ?>", {});
            });
            //-->
        </script>
        <script type="text/javascript">
            //<!--
            $(function () {
                if (!window.parent || !window.parent.bridge) {
                    return;
                }
                var bridge = window.parent.bridge;
                bridge.event("login");
            });
            //-->
        </script>
        <script type="text/javascript">
            //<!--
            $(function () {
                if (!window.parent || !window.parent.bridge) {
                    return;
                }
                var bridge = window.parent.bridge;
                bridge.event("loaded");
            });
            //-->
        </script>
        <script>
            $(document).ready(function () {
                $.fn.ajaxValidation.defaults.url = "<?php echo $mellon_url; ?>ajax/form-validate?msid=<?php echo rawurlencode($_SESSION['mellon_msid'] ?? ''); ?>&msname=msid";
            });
        </script>
    </head>

    <body class="mellon-dialog c-authentication-login ltr">
        <div class="container">
            <form action="<?php echo $mellon_url; ?>authentication/login/" method="POST" name="login" class="form-horizontal tk-sign-process" id="login">
                <fieldset class="social-login">
                    <legend>Login with</legend>
                </fieldset>
                <div class="form-group form-group-type-text">
                    <label>or</label>
                    <input type="text" name="email" class="form-control" placeholder="Email address" data-placement="top" value="">      
                </div>
                <div class="form-group form-group-type-password">
                    <input type="password" name="password" class="form-control" placeholder="Password" data-placement="bottom" value="">        
                </div>
                <div class="form-group form-group-type-submit">

                    <input name="submit" type="submit" class="btn btn-primary tracking" value="Log in" data-trackingeventname="mellon.click_on_login">        
                </div>
            </form>
            <div class="login-extra">
                <div class="login-extra-reset-password left">
                    <a href="<?php echo $base; ?>/authentication/password-reset-request/applicationDomain/www.kingdoms.com/applicationPath/%2Fcom%2F/applicationInGame/0/applicationId/travian-ks/applicationCountryId/en/applicationInstanceId/portal-en/applicationLanguageId/en_US/applicationCookieEnabled/1?msid=<?php echo $_SESSION['mellon_msid']; ?>&msname=msid">
                        Forgot password        </a>
                </div>
                <div>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</div>
                <div class="login-extra-create-account right">
                    New player?        <a href="<?php echo $base; ?>/registration/index/applicationDomain/www.kingdoms.com/applicationPath/%2Fcom%2F/applicationInGame/0/applicationId/travian-ks/applicationCountryId/en/applicationInstanceId/portal-en/applicationLanguageId/en_US/applicationCookieEnabled/1?msid=<?php echo $_SESSION['mellon_msid']; ?>&msname=msid">
                        Sign up        </a>
                </div>
            </div>

            <script type="text/javascript">
                $('body').width('386px');

                $(document).ready(function () {
                    var legend = $('.social-login legend');
                    legend.replaceWith($('<div class="form-group">' + legend[0].innerHTML + '</div>'));

                    if ($('.tk-sign-process .form-group-type-text .list-group').length) {
                        $('.list-group').insertBefore('input[name=email]');
                    }

                    $('input[name=email], input[name=password]').ajaxValidation({formName: 'login'});
                });

            </script>

        </div> <!-- /container -->

        <!-- facebook layer -->
        <div id="fb-root"></div>

    </body>
</html>
