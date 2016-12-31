# Statamic Boilerplate
A bootstrap for quick development and deployment with [Envoyer.io](https://envoyer.io)

## This is a WIP

## Setting up deployment in Envoyer
- Go through the basic setup of adding your server and repository to Envoyer. Choose "other" when asked for the type of application
- Copy over the `.env` details into the "Manage Environment" of Envoyer
- Create a `storage` folder in your deployment directory (usually httpdocs)
- Copy over the storage directory of your project once to the newly created storage folder  
*If anyone has a better, automated solution to this, let me know!*
- Add a linked folder in Envoyer with the following settings:
    - Create Link At: `storage`
    - To Folder: `storage`
- Add the following deployment hooks
    - Before Activate New Release
        - Yarn  
        ```
        cd {{release}}
        yarn
        ```
        - Gulp
        ```
        cd {{release}}
        gulp --production
        ```
    - After Activate New Release
        - Clear cache  
        *Depending on how much caching you enable*  
         ```
         cd {{release}}
         php please clear:cache
         php please clear:stache
         php please clear:static
         ```
 - Point your website root to the `public` folder

## Changes to the file structure
- Running Statamic above webroot to prevent accidents
- A storage directory has been created in the root
- The site/content folder is now in storage/content
- The site/assets folder is now in storage/assets
- The site/users folder is nwo in storage/users
- Split the theme folder off and only make the compiled assets available
    - This has the benefit of not having all your templates publicly accessible

This allows for a deployment workflow where the `storage` folder is symlinked between releases and changes that are made through the control panel are kept.

## Changes to settings
The following settings have been moved to the `.env` file for configuration between dev/production
```
APP_ENV=dev
APP_DEBUG=true
APP_DEBUGBAR=false
APP_URL=http://statamic-boilerplate.dev
APP_KEY=SomeRandomStringWith32Characters

STACHE_UPDATE=true
STATIC_CACHE_ENABLED=false
STATIC_CACHE_TYPE=cache
STATIC_CACHE_LENGTH=86400
STATIC_CACHE_FILE_PATH=static
STATIC_CACHE_IGNORE_QUERY=true
```
These settings cannot be changed through the control panel.

## Included Add-ons
90% Of sites that we build need the following Add-ons so they're included by default, these can be removed easily:
- Sitemap
- CookieConsent

Take a look at our [public repositories](https://github.com/marbles) to see if we might have another addon that might be useful to you.

