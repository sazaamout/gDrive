<?php

/**
 * Promotion Engine - Phase II
 * $Id$
 *
 * @package         PE (Promotion Engine)
 * @subpackage      Models
 * @copyright       (c)SCA Interactive / Blast Promotions Inc.
 * 
 * 
 * @version         $Revision$
 * @modifiedby      $Author$
 * @lastmodified    $Date$
 *
 */
require_once(VENDORS_PATH.'/google-api-php-v2.2.1/vendor/autoload.php');

require_once(EPATH . '/models/model.php');

  
define('SCOPES', implode(' ', array( Google_Service_Drive::DRIVE )));
  
class DriveModel extends Model {

    // this is the user that will be used to perform the actaul operations
    // make sure that this user have all of the needed permissions
    private $GoogleUser            = 'user@gmail.com';
    private $ApplicationName       = 'Service Accounts Tutorial';
    private $client;
    private $service;
    
    
    // -----------------------------------------------------------------------------------
    // Constructor
    // -----------------------------------------------------------------------------------
    function __construct() {
        parent::__construct();

        // set the credential array. All of these information can be found in the json file that was provied to you when you create
        // a service account
        $config = array(
            'type'           => 'service_account',
            'project_id'     => 'XXX',
            'private_key_id' => 'XXX',
            'client_email'   => 'XXX',
            'client_id'      => 'XXX',
            'auth_uri'       => 'https://accounts.google.com/o/oauth2/auth',
            'token_uri'      => 'https://accounts.google.com/o/oauth2/token',
            'private_key'    => "-----BEGIN PRIVATE KEY-----\nXXX\n-----END PRIVATE KEY-----\n",
            'auth_provider_x509_cert_url' => 'https://www.googleapis.com/oauth2/v1/certs',
            'client_x509_cert_url'        => 'XXX'
        );
    
        // create the new google client
        $this->client = new Google_Client();
        $this->client->setAuthConfig($config);
        $this->client->useApplicationDefaultCredentials(); 
        $this->client->setApplicationName($this->ApplicationName);
        $this->client->setScopes(SCOPES); 
        $this->client->setAccessType("offline");
        $this->client->setSubject($this->GoogleUser);

        // create the service
        $this->service = new Google_Service_Drive($this->client);
    }
  
  
    // -----------------------------------------------------------------------------------
    // List Folders
    // -----------------------------------------------------------------------------------
    // this function will search inside the parent dir only and wont recurse inside the subdir
    // the $parent is the id not the name
    
    function listFolders( $parent ) {

        $retval = array(
            'success' => 0,
            'errorMessage' => null,
            'data'  => array()
        );

        if (empty($parent)){
            $retval['success']      = 0;
            $retval['errorMessage'] = "parent is not specified";
            return $retval;
        }

        $pageToken = null;
        do {
            $response = $this->service->files->listFiles(array(
                'q' => "mimeType='application/vnd.google-apps.folder' and trashed=false and '" . $parent . "' in parents",
                'spaces' => 'drive',
                'pageToken' => $pageToken,
                'fields' => 'nextPageToken, files(id, name)',
                'supportsTeamDrives' => true,
                'includeTeamDriveItems' => true
            ));

            foreach ($response->files as $file) {
                $retval['data'][$file->id] = $file->name;
            }
            
            $pageToken = $response->nextPageToken;
            
        } while ($pageToken != null);

        $retval['success'] = 0;
        return $retval;
    }

    // -----------------------------------------------------------------------------------
    // creating a folder inside the $parent. The $name is the title of the folder
    // -----------------------------------------------------------------------------------
    public function createFolder( $name, $parent ) {
        
        if (empty($parent))
            return;

        $fileMetadata = new Google_Service_Drive_DriveFile(array(
            'name' => "$name",
            'parents' => array($parent),
            'mimeType' => 'application/vnd.google-apps.folder'));

        $file = $this->service->files->create($fileMetadata, array(
            'fields' => 'id',
            'supportsTeamDrives' => true
        ));

        return $file->id;
    }


    // -----------------------------------------------------------------------------------
    // check if a folder exist and return the Id of that folder
    // -----------------------------------------------------------------------------------
    public function isExistFolder( $name, $parent ) {

        $retval = array(
            'success' => 0,
            'errorMessage' => null,
            'data'  => array()
        );

        if (empty($parent)) {
            $retval['success'] = 0;
            $retval['errorMessage'] = 'parent is not specified';
            return $retval;
        }

        $pageToken = null;

        do {
            $response = $this->service->files->listFiles(array(
                'q' => "mimeType='application/vnd.google-apps.folder' and trashed=false and '" . $parent . "' in parents",
                'spaces' => 'drive',
                'pageToken' => $pageToken,
                'fields' => 'nextPageToken, files(id, name)',
                'supportsTeamDrives' => true,
                'includeTeamDriveItems' => true
            ));

            foreach ($response->files as $file) {
                if ( $file->name === "$name") {
                    $retval['success'] = 1;
                    $retval['data']['id'] = $file->id;
                    return $retval;
                }
            }
                $pageToken = $response->nextPageToken;

        } while ($pageToken != null);

        // nothing was found, then return false
        $retval['success'] = 0;
        $retval['errorMessage'] = 'no entry was found';
        return $retval;

    }
    
    // -----------------------------------------------------------------------------------
    // function that will return activities for a given $parent
    // -----------------------------------------------------------------------------------
    
    function showActivities( $clientId, $projectId ){
        
        $retval = array(
            'success' => 0,
            'errorMessage' => null,
            'data'  => array()
        );
        
        
        if ( !empty($clientId) ){
            $parent = $clientId;
        }
               
        if ( !empty($projectId) ){
            $parent = $projectId;
        }
               
        $savedPageToken = '4513641';
        
        $response = $this->service->changes->getStartPageToken(array("supportsTeamDrives" => true));
        printf("Start token: %s\n", $response->startPageToken);
        return;
        
        # Begin with our last saved start token for this user or the
        # current token from getStartPageToken()
        $changed = array();
        $removed = array();
        
        $pageToken = $savedPageToken;
        while ($pageToken != null) {
            $response = $this->service->changes->listChanges($pageToken, array(
                'spaces' => 'drive',
                'supportsTeamDrives' => true,
                'includeTeamDriveItems' => true
            ));
            
            // print all of the files that got chagned
            foreach ($response->changes as $change) {
                // Process change
                if ( !$change->removed ){
                    //echo "Time:[$change->time], Kind:[$change->kind], Type:[$change->type], Name:[".$change->file['name']."], Id:[$change->fileId]</br>";
                    $changed[$change->fileId] = "Time:[$change->time], Kind:[$change->kind], Type:[$change->type], Name:[".$change->file['name']."]";
                }
            }
            // print all of the files that got removed
            foreach ($response->changes as $change) {
                // Process change
                if ( $change->removed ){
                    //echo "Time:[$change->time], Kind:[$change->kind], Type:[$change->type], Name:[".$change->file['name']."], Id:[$change->fileId]</br>";
                    $removed[$change->fileId] = "Time:[$change->time], Kind:[$change->kind], Type:[$change->type]";
                }
            }
            
            if ($response->newStartPageToken != null) {
                // Last page, save this token for the next polling interval
                $savedStartPageToken = $response->newStartPageToken;
            }
            $pageToken = $response->nextPageToken;
        }
        
        echo "------------------------------------------ </br>";
        echo "All Removed Files </br>";
        echo "------------------------------------------ </br>";
        pr($removed);
        
        echo "</br>------------------------------------------ </br>";
        echo "All Changed Files </br>";
        echo "------------------------------------------ </br>";
        pr($changed);

    }
    
    
    // -----------------------------------------------------------------------------------
    // funcion used to move a file to a new parent
    // -----------------------------------------------------------------------------------
    
    function move( $fileId, $parent ){
        // move this file
        $emptyFileMetadata = new Google_Service_Drive_DriveFile();
        // Retrieve the existing parents to remove
        $fileInfo = $this->service->files->get($fileId, array('fields' => 'parents'));
        $previousParents = join(',', $fileInfo->parents);
        // Move the file to the new folder
        $fileInfo = $this->service->files->update($fileId, $emptyFileMetadata, array(
            'addParents' => $parent,
            'removeParents' => $previousParents,
            'fields' => 'id, parents',
            'supportsTeamDrives' => true,
        ));
    }
    
    // -----------------------------------------------------------------------------------
    // delete a folder or a file
    // -----------------------------------------------------------------------------------
    function delete( $fileId, $parent ){
        $this->service->files->delete( $fileId, array('supportsTeamDrives' => true) );
    }
    
}
