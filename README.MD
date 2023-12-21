<h1 align="center">dropbox-simple-upload-cli</h1>

------

This tiny PHP application has been created for easy file uploads to Dropbox storage. One of its possible use cases is uploading backups (I use it specifically for this purpose).

To begin, you need to create a Dropbox app to provide access to your storage. Then, once you have the app key and secret, you can proceed with the 'init' command.

Note! Don't forget to grant read and write permissions for your app!

### Initialization command
Grants access rights, saves service information, and creates a configuration file.
Run the initial configuration script (only needed for the first run) using the command and follow all the described steps:
```shell script
/var/www/dropbox-simple-upload-cli$ php upload.php init

Dropbox upload initialization script.
=====================================

 ! [NOTE] Configuration file will be saved in: /var/www/dropbox-simple-upload-cli/config.json

 Please input APP_KEY:
 > jdh2fnhrj4duasjd

 Please input APP_SECRET:
 > pol8jhfiospa3odk

 1. Go to: https://www.dropbox.com/oauth2/authorize?client_id=jdhbfnhrjkduasjd&response_type=code&token_access_type=offline
 2. Click "Allow" (you might have to log in first).
 3. Copy the authorization code.

 Enter the authorization code here:
 > EZrGqGLIPSQAAAAAAAACYNfYzEHn_kEGm4U1h7CUXMA


 [OK] Welcome, New User!
      It's done! You can now proceed to upload files to your Dropbox storage.
```

### File upload command
Once the configuration is set up, you can start uploading files using the command 'php upload.php file "file_path" ["upload_path"]'.
```shell script
php upload.php file_path [upload_path]
--- EXAMPLE ---
05e61217b9d5:/var/www/dropbox-simple-upload-cli$ php upload.php file myfile.txt /files/

Dropbox upload file.
====================

 ! [NOTE] Token is valid.


 [OK] Done!
```

## To-do list:
1. Implement a configuration validation method.
2. Create a Configuration class for managing the configuration file.
3. Refactor logic related to Dropbox workflow into the DropboxProvider class.
4. Implement the ProviderInterface.
5. Add GoogleDrive support.