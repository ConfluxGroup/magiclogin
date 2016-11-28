#Magic Login - BETA
Simple and password-less login for [Craft CMS](https://craftcms.com/).

##Installation
Download Magic Login from [GitHub](https://github.com/aberkie/magiclogin) and upload the `magiclogin` directory to your `craft/plugins` folder. Don't forget to install the plugin in your site's admin (yoursite.com/admin/settings/plugins).

##Usage

Upon installation, you will need to create a set of templates for Magic Login to use for its login form and emails.

###Login Form
In the template where you want to create your login form, use code like this

```
<p>Enter your email address below to receive a Magic Login link.</p>

<p>The link will expire after {{settings.linkExpirationTime}} minutes and can only be used once.</p>

{% if status == 'success' %}
	<p class="{{status}}">Check your email for your login link.</p>
{% elseif status == 'fail' %}
	<p class="{{status}}">Try again. Something went wrong.</p>
{% endif %}

<form method="post" action="" accept-charset="UTF-8">
	<input type="hidden" name="action" value="magicLogin/authentication/login">
	<input type="hidden" name="redirect" value="/admin">
	<label for="email">Email Address: </label>
	<input id="email" type="text" name="email" />

    <input type="submit" value="{{ 'Get Link'|t }}">
</form>
```

####Status variables
The template variable `status` will return either `success` or `fail`. This variable can be used in a conditional in order to display a response to the user after submitting their email address.

####Redirecting After Login
The settings for Magic Login allow you to configure a default redirect after login. If you want to override this on a per-form basis, include a hidden field called `redirect` specifying the path where you want the user redirected after login.


###Email Template(s)
Create a template for Magic Login to use for your login emails inside the `craft/templates` directory. 

Update the plugin settings in the control panel with your template path. Note that you can define both a plaintext and an HTML template path. The plaintext template is required, while the HTML template is optional.

Your email template should look something like this:

```
Hello {{ user.friendlyName }},

Here is your Magic Login link. Click it fast, because it will it expire in {{ linkExpirationTime }}.

{{ link }}
```

####Link Expiration Time
The variable `{{ linkExpirationTime }}` will output the number of minutes which the magic link will remain active before expiring.

####Link
Obviously, it's important to include the actual magic link in your email. The `{{ link }}` variable will output this URL.

####User Information
The user object is also included in the template variables, so you can customize your email with the user's name or other relevant information.

##Settings
Magic Login allows you to set a number of additional settings.

###Magic Login Expiration Time 

Adjust "Magic Login Expiration Time" to allow links to be used for a certain amount of time after being requested. The default is 5 minutes.

###Default Redirect URL after Login
Adjust "Redirect URL after Login" to change the default URL where the user is sent after they login with a Magic Login link. The default is `/admin`. This setting is overridden by the `redirect` field in the login form, if included.

###URL path for Magic Links
The default path for magic links is `/magiclogin/auth`. You can override this path using this setting.

###Subject line for Magic Login email
This setting defines the subject line used for the magic link emails.

###Link Error Path
If the link has an error, due to expiration or other circumstances, this setting defines where the user will be redirected, usually your login form.

##Disclaimer
Usage of this plugin does not guarantee any security for your Craft CMS site. This is an open-source project and has not been vetted by security or encryption experts.