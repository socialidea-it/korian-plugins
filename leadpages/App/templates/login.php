<?php
if (! defined('ABSPATH')) {
    exit;
}
?>

<link rel="stylesheet" href="https://leadpage-test.appspot.com/static/lp10016456456456/bootstrap/lp3/css/bootstrap.min.css"/>
<link rel="stylesheet" href="https://leadpage-test.appspot.com/static/lp10016456456456/build/css/lp.css"/>
<link rel="stylesheet" href="https://leadpage-test.appspot.com/static/lp10016456456456/min/jquery-ui-1.9.2.custom.min.css" type="text/css"/>
<link href='https://fonts.googleapis.com/css?family=Roboto:400,100,300,500,700' rel='stylesheet' type='text/css'>

<style>
    .login_wrapper {
        display: flex;
        font-family: 'Roboto', Arial !important;
        color: #fff;
        height: 900px;
        /*to fix inside wordpress admin correctly we need a -20px margin left*/
        margin-left:-20px;
    }

    .login_wrapper h1 {
        font-size: 24px;
        color: #FFFFFF;
        line-height: 36px;
        font-weight: 400;
        margin:0 auto;
        padding:0 auto;
    }

    .login_wrapper .login_left_lower h1 {
        margin-top: 0px;
        margin-bottom: 0px;
        padding-bottom: 0px;
    }

    .login_wrapper h3 {
        font-size: 14px;
        color: #E6E9EF;
        letter-spacing: 0.96px;
        line-height: 24px;
        font-weight: 200;
    }
    .login_left_upper .upper-tagline{
        margin:0;
        padding:0;

    }

    .yellow_button {
        background: #FED200;
        border-radius: 100px;
        font-size: 14px;
        color: #020020;
        letter-spacing: 0.89px;
        text-transform: uppercase;
        width: 144px;
        height: 56px;
        text-align: center;
        padding-top: 18px;
        margin-top: 26px;
        display: block;
        font-weight: 200;
    }

    .transparent_button {
        background: transparent;
        border: 2px solid #fff;
        border-radius: 100px;
        font-size: 14px;
        color: #fff;
        letter-spacing: 0.89px;
        text-transform: uppercase;
        width: 144px;
        height: 56px;
        text-align: center;
        margin-top: 23px;
        padding-top: 18px;
        display: block;
        font-weight: 200;
    }

    .transparent_button:hover {
        color: #fff;
        font-weight: 400;
    }

    .login_wrapper hr {
        width: 43px;
        height: 1px;
        color: #fff;
        margin: 6px 0;
    }

    .feature_img {
        margin-top: 36px;
    }

    .login_left_lower {
        background-color: #0069FF;
        background-image: url('https://static.leadpagestest.net/images/lp_plugin/left_column_lower.png');
        background-position: right bottom;
        background-repeat: no-repeat;
        padding: 50px 55px;
        height:300px;
    }

    .login_left_lower p {
        font-size: 16px;
        line-height: 24px;
        color: #fff;
        font-weight: 200;
        padding-bottom: 0px;
        margin-bottom: 0px;
    }

    .lego-login__right {
        width: 841px !important;
    }

    #login-form {
        width: 358px;
    }

    .login_form_wrapper {
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        align-items: center;
        margin-top: 170px;
    }

    .sign-in-container {
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .sign-in-container .forgot-password {
        font-size: 14px;
        line-height: 24px;
        color: #797F89;
    }
    .login_wrapper .lp-privacy_policy{
        font-size:14px;
        line-height:24px;
        color: #797F89;
    }
    .login_wrapper .lp-privacy_policy a{
        font-size:14px;
        line-height:24px;
        color: #797F89;
        text-decoration: underline;
    }
</style>

<div class="login_wrapper">
    <!-- Right side of page -->
    <div class="lego-login__right" style="flex-grow:0; justify-content: space-between; margin-left:0; margin-right:0; height:900px;">
        <div class="login_form_wrapper">
            <img class="lp-logo" src="https://v5-24-6-dot-lead-pages.appspot.com/static/lp1498594249/img/logos/logo_standard.svg">
            <div id="login-form">
                <form novalidate="novalidate" class="form lp_login_form" name="login-form" method="post"
                      action="admin-post.php">

                    <br/>
                    <!-- FORM FIELDS -->
                    <div class="form__field-container">
                        <input class="required email form__field" type="email" name="username" placeholder="Email Address"
                               required/>
                    </div>
                    <div class="form__field-container">
                        <input class="required form__field" type="password" name="password" placeholder="Password"/>
                        <span class="form-control-feedback"></span>
                    </div>
                    <!-- DO NOT REMOVE both of these need to be added to make login form work in wordpress -->
                    <input type="hidden" name="action" value="leadpages_login_form"/>
                    <?php wp_nonce_field('leadpages_login'); ?>

                    <!-- FORM FIELDS END -->

                    <!-- SIGN IN CONTAINER -->
                    <div class="sign-in-container">
                        <a class="forgot-password" href=""></a>
                        <button name="form-submit" class="lego-btn lego-btn--icon-right" type="submit"
                                id="lego-login-submit" data-lp-icon="&#xe204;"
                                data-loading-text="Logging in &hellip;">
                            Sign In
                        </button>
                    </div>
                    <!-- SIGN IN END -->
                    <hr style="width:100%; color: #797F89; margin-top:40px; margin-bottom:40px;">
                    <p class="lp-privacy_policy">By clicking Sign In, you agree to the <a href="http://www.leadpages.net/legal/" target="_blank">Terms of Service</a> and <a href="http://www.leadpages.net/legal#section_11" target="_blank">Privacy Policy Agreement</a></p>
            </form>
            </div>
        </div>
        <div class="lego-login__footer">
                <span class="lp-login-asset copyright">&copy; 2010-2020 Leadpages (US), Inc. All rights reserved. <a
                    href="http://www.leadpages.net/legal/" target="_blank">Terms of Service</a><br>
                    Leadpages&reg;, Leadbox&reg;, Leadboxes&reg;, Leadbars&reg;, Leadlinks&reg;, Leaddigits&reg; are registered trademarks of Leadpages (US), Inc.<br></span>
        </div>


        <!-- End right side of page -->
    </div>
    <script type="text/javascript" src="https://leadpage-test.appspot.com/static/lp10016456456456/min/dist/jquery.js"></script>

