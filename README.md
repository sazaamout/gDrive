# gDrive
This is just an example class to demostrate how to use google drive API including googleTeamDrive and to demostrate how to service account. 

# prerequisites
1. a service account must be setup, follow the following link to setup a service account. 
https://support.google.com/a/answer/7378726?hl=en
2. go to Credentials > Manage Service Account then 'View Client ID'. copy the client ID to clipboard
3. launch the GSuite Admin Console
4. Go to Security>Advanced Settings>Mange API client access.
5. Paste the Client ID in the Client Name field, add the following API scopes and click Authorize:
https://www.googleapis.com/auth/admin.directory.group, https://www.googleapis.com/auth/admin.directory.user, https://www.googleapis.com/auth/calendar.readonly, https://www.googleapis.com/auth/drive 
  
