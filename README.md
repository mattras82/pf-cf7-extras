# PublicFunction CF7 Extras Plugin #

 - Version 1.0.0
 
### WordPress plugin for adding additional functionality to the Contact Form 7 plugin. This includes extra validation, tracking variable, and more. ###

### How do I get set up? ###
- Clone contents of this repository and create "pf-cf7-extras" folder under wp-content/plugins/
- Enable plugin under Wordpress Admin
- Add following variables to admin email template
    * [user_ip] - User's IP
    * [user_referrer] - The page user came from
    * [user_path] - Sequence of addresses that user viewed on the site
    * [user_ppc] - Value of PPC keyword - this is set in settings
- You can customize settings in Settings -> PF CF7 User Info menu
- You __must__ enable PPC or Geolocation to use them
- The default Geolocation endpoint is set in the class, otherwise define one in the Settings -> PF CF7 User Info menu
- Current endpoint as of 02/13/2019: https://ip.goldencomm.com/lookupJSON.aspx

## Changelog ##

### v.1.0.0
- Initializing plugin.
