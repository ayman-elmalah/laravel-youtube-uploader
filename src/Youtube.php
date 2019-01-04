<?php

namespace AymanElmalah\YoutubeUploader;

use Google_Client;
use Google_Service_YouTube;
use Google_Service_YouTube_VideoSnippet;
use Google_Service_YouTube_VideoStatus;
use Google_Service_YouTube_Video;
use Google_Http_MediaFileUpload;
use Google_Service_Exception;
use Google_Service_YouTube_PlaylistSnippet;
use Google_Service_YouTube_PlaylistStatus;
use Google_Service_YouTube_Playlist;
use Google_Service_YouTube_ResourceId;
use Google_Service_YouTube_PlaylistItemSnippet;
use Google_Service_YouTube_PlaylistItem;
use Google_Service_YouTube_ChannelBannerResource;
use Exception;

class Youtube
{
    /**
     * Oauth client id
     *
     * @var OAUTH2_CLIENT_ID
     */
    private $OAUTH2_CLIENT_ID;

    /**
     * Oauth client secret
     *
     * @var OAUTH2_CLIENT_ID
     */
    private $OAUTH2_CLIENT_SECRET;

    /**
     * redirection url
     *
     * @var redirect
     */
    private $redirect;

    /**
     * Youtube object
     *
     * @var youtube
     */
    private $youtube;

    /**
     * Client object
     *
     * @var client
     */
    private $client;

    /**
     * Vidoe title
     *
     * @var video_title
     */
    private $video_title;

    /**
     * Vidoe description
     *
     * @var video_description
     */
    private $video_description;

    /**
     * Vidoe tags
     *
     * @var video_description
     */
    private $video_tags;

    /**
     * Vidoe category id
     *
     * @var video_category_id
     */
    private $video_category_id;

    /**
     * Uploaded videon id
     *
     * @var uploaded_video_id
     */
    private $uploaded_video_id;

    /**
     * Created playlist id
     *
     * @var created_playlist_id
     */
    private $created_playlist_id;

    /**
     * Token session
     *
     * @var tokenSessionKey
     */
    private $tokenSessionKey;

    /**
     * Constructor
     *
     * @return void
     */
    public function __construct()
    {
        session_start();
        $this->OAUTH2_CLIENT_ID = env('GOOGLE_CLIENT_ID');
        $this->OAUTH2_CLIENT_SECRET = env('GOOGLE_CLIENT_SECRET');
    }

    /**
     * Handling client
     *
     * @return object
     */
    public function client()
    {
        if ($this->client == null) {
            $client = new Google_Client();
            $client->setClientId($this->OAUTH2_CLIENT_ID);
            $client->setClientSecret($this->OAUTH2_CLIENT_SECRET);
            $client->setScopes('https://www.googleapis.com/auth/youtube');
            $client->setRedirectUri($this->redirect);

            // Define an object that will be used to make all API requests.
            $this->youtube = new Google_Service_YouTube($client);

            // Check if an auth token exists for the required scopes
            $this->tokenSessionKey = 'token-' . $client->prepareScopes();
            if (isset($_GET['code'])) {
                if (strval($_SESSION['state']) !== strval(request()->state)) {
                    throw new Exception('The session state did not match.');
                }
                $client->authenticate(request()->code);
                $_SESSION[$this->tokenSessionKey] = $client->getAccessToken();
                header('Location: ' . $this->redirect);
            }
            if (isset($_SESSION[$this->tokenSessionKey])) {
                $client->setAccessToken($_SESSION[$this->tokenSessionKey]);
            }

            $this->client = $client;
        }

        return $this->client;

    }

    /**
     * Get youtube auth url
     *
     * @return url
     */
    public function AuthUrl()
    {
        $client = $this->client();

        // If the user hasn't authorized the app, initiate the OAuth flow
        $state = mt_rand();
        $client->setState($state);
        $_SESSION['state'] = $state;
        return $client->createAuthUrl();
    }

    /**
     * Set redirect url
     *
     * @return object
     */
    public function setRedirectUrl($url)
    {
        $this->redirect = $url;

        return $this;
    }

    /**
     * Upload the given video to youtube
     * @params string $video
     * @params array $params
     *
     * @return void
     */
    public function upload($video, $params = [])
    {
        if (!file_exists($video)) {
            throw new Exception('Video doesn\'t exist : ' . $video);
        }

        $client = $this->client();
        $youtube = $this->youtube;

        if (is_array($params)) {
            $this->video_title = array_key_exists('title', $params) ? $params['title'] : 'Video uploaded by Ayman Elmalah Youtube Uploader';
            $this->video_description = array_key_exists('description', $params) ? $params['description'] : 'The package will help you to upload videos to youtube and making youtube api much easier';
            $this->video_tags = array_key_exists('tags', $params) ? $params['tags'] : [];
            $this->video_category_id = array_key_exists('video_category_id', $params) ? $params['video_category_id'] : '';
        } else {
            throw new Exception('second parameter must be an array');
        }

        if ($client->getAccessToken()) {
            try {
                $videoPath = $video;
                // Create a snippet with title, description, tags and category ID
                // Create an asset resource and set its snippet metadata and type.
                // This example sets the video's title, description, keyword tags, and
                // video category.
                $snippet = new Google_Service_YouTube_VideoSnippet();
                $snippet->setTitle($this->video_title);
                $snippet->setDescription($this->video_description);
                $snippet->setTags($this->video_tags);
                // Numeric video category. See
                // https://developers.google.com/youtube/v3/docs/videoCategories/list
                $snippet->setCategoryId($this->video_category_id);
                // Set the video's status to "public". Valid statuses are "public",
                // "private" and "unlisted".
                $status = new Google_Service_YouTube_VideoStatus();
                $status->privacyStatus = "public";
                // Associate the snippet and status objects with a new video resource.
                $video = new Google_Service_YouTube_Video();
                $video->setSnippet($snippet);
                $video->setStatus($status);
                // Specify the size of each chunk of data, in bytes. Set a higher value for
                // reliable connection as fewer chunks lead to faster uploads. Set a lower
                // value for better recovery on less reliable connections.
                $chunkSizeBytes = 1 * 1024 * 1024;
                // Setting the defer flag to true tells the client to return a request which can be called
                // with ->execute(); instead of making the API call immediately.
                $client->setDefer(true);
                // Create a request for the API's videos.insert method to create and upload the video.
                $insertRequest = $youtube->videos->insert("status,snippet", $video);
                // Create a MediaFileUpload object for resumable uploads.
                $media = new Google_Http_MediaFileUpload(
                    $client,
                    $insertRequest,
                    'video/*',
                    null,
                    true,
                    $chunkSizeBytes
                );
                $media->setFileSize(filesize($videoPath));
                // Read the media file and upload it chunk by chunk.
                $status = false;
                $handle = fopen($videoPath, "rb");
                while (!$status && !feof($handle)) {
                    $chunk = fread($handle, $chunkSizeBytes);
                    $status = $media->nextChunk($chunk);
                }
                fclose($handle);
                // If you want to make other calls after the file upload, set setDefer back to false
                $client->setDefer(false);

                // Set ID of the Uploaded Video
                $this->uploaded_video_id = $status['id'];
            } catch (Google_Service_Exception $e) {
                throw new Exception($e->getMessage());
            } catch (Google_Exception $e) {
                throw new Exception($e->getMessage());
            }
            $_SESSION[$this->tokenSessionKey] = $client->getAccessToken();
        } else {
            throw new Exception('You are not authorized to do that');
        }

        return $this;
    }

    /**
     * Get uploaded video id
     *
     * @return string
     */
    public function uploadedVideoId()
    {
        return $this->uploaded_video_id;
    }

    /**
     * Update video by geiven id
     * @params string video_id
     * @params array params
     *
     * @return void
     */
    public function updateTags($video_id, $tags = [])
    {
        $client = $this->client();
        $youtube = $this->youtube;

        if (is_array($tags)) {
            $this->video_tags = $tags;
        } else {
            throw new Exception('second parameter must be an array');
        }

        if ($client->getAccessToken()) {
            try {
                // Call the API's videos.list method to retrieve the video resource.
                $listResponse = $youtube->videos->listVideos("snippet",
                    array('id' => $video_id));

                // If $listResponse is empty, the specified video was not found.
                if (empty($listResponse)) {
                    throw new Exception('Can\'t find a video with video id: ' . $video_id);
                } else {
                    // Since the request specified a video ID, the response only
                    // contains one video resource.
                    $video = $listResponse[0];
                    $videoSnippet = $video['snippet'];
                    $tags = $videoSnippet['tags'];

                    // Preserve any tags already associated with the video. If the video does
                    // not have any tags, create a new list. Replace the values "tag1" and
                    // "tag2" with the new tags you want to associate with the video.
                    if (!empty($this->video_tags)) {
                        $tags = $this->video_tags;
                    }

                    // Set the tags array for the video snippet
                    $videoSnippet['tags'] = $tags;

                    // Update the video resource by calling the videos.update() method.
                    $updateResponse = $youtube->videos->update("snippet", $video);

                    $responseTags = $updateResponse['snippet']['tags'];
                }
            } catch (Google_Service_Exception $e) {
                throw new Exception($e->getMessage());
            } catch (Google_Exception $e) {
                throw new Exception($e->getMessage());
            }

            $_SESSION[$this->tokenSessionKey] = $client->getAccessToken();
        } else {
            throw new Exception('You are not authorized to do that');
        }
        return $this;
    }

    /**
     * Create playlist
     *
     * @params string $title
     * @params text $description
     * @return void
     */
    public function createPlaylist($title, $description)
    {
        $client = $this->client();
        $youtube = $this->youtube;

        if ($client->getAccessToken()) {
            try {
                // 1. Create the snippet for the playlist. Set its title and description.
                $playlistSnippet = new Google_Service_YouTube_PlaylistSnippet();
                $playlistSnippet->setTitle($title);
                $playlistSnippet->setDescription($description);

                // 2. Define the playlist's status.
                $playlistStatus = new Google_Service_YouTube_PlaylistStatus();
                $playlistStatus->setPrivacyStatus('public');

                // 3. Define a playlist resource and associate the snippet and status
                // defined above with that resource.
                $youTubePlaylist = new Google_Service_YouTube_Playlist();
                $youTubePlaylist->setSnippet($playlistSnippet);
                $youTubePlaylist->setStatus($playlistStatus);

                // 4. Call the playlists.insert method to create the playlist. The API
                // response will contain information about the new playlist.
                $playlistResponse = $youtube->playlists->insert('snippet,status',
                    $youTubePlaylist, array());

                $this->created_playlist_id = $playlistResponse['id'];
            } catch (Google_Service_Exception $e) {
                throw new Exception($e->getMessage());
            } catch (Google_Exception $e) {
                throw new Exception($e->getMessage());
            }
            $_SESSION[$this->tokenSessionKey] = $client->getAccessToken();
        } else {
            throw new Exception('You are not authorized to do that');
        }

        return $this;
    }

    /**
     * Get created playlist id
     *
     * @return string
     */
    public function createdPlaylistId()
    {
        return $this->created_playlist_id;
    }

    /**
     * Insert video to playlist
     *
     * @params string $video_id
     * @params string _playlist_id
     * @return void
     */
    public function VideoToPlaylist($video_id, $playlist_id)
    {
        $client = $this->client();
        $youtube = $this->youtube;

        if ($client->getAccessToken()) {
            try {
                // 5. Add a video to the playlist. First, define the resource being added
                // to the playlist by setting its video ID and kind.
                $resourceId = new Google_Service_YouTube_ResourceId();
                $resourceId->setVideoId($video_id);
                $resourceId->setKind('youtube#video');

                // Then define a snippet for the playlist item. Set the playlist item's
                // title if you want to display a different value than the title of the
                // video being added. Add the resource ID and the playlist ID retrieved
                // in step 4 to the snippet as well.
                $playlistItemSnippet = new Google_Service_YouTube_PlaylistItemSnippet();
                // $playlistItemSnippet->setTitle('First video in the test playlist');
                $playlistItemSnippet->setPlaylistId($playlist_id);
                $playlistItemSnippet->setResourceId($resourceId);

                // Finally, create a playlistItem resource and add the snippet to the
                // resource, then call the playlistItems.insert method to add the playlist
                // item.
                $playlistItem = new Google_Service_YouTube_PlaylistItem();
                $playlistItem->setSnippet($playlistItemSnippet);
                $playlistItemResponse = $youtube->playlistItems->insert(
                    'snippet,contentDetails', $playlistItem, array());
            } catch (Google_Service_Exception $e) {
                throw new Exception($e->getMessage());
            } catch (Google_Exception $e) {
                throw new Exception($e->getMessage());
            }
            $_SESSION[$this->tokenSessionKey] = $client->getAccessToken();
        } else {
            throw new Exception('You are not authorized to do that');
        }

        return $this;
    }

    /**
     * Update banner
     * @params string $image
     *
     * @return void
     */
    public function updateThumbnail($video_id, $image)
    {
        if (!file_exists($image)) {
            throw new Exception('Image doesn\'t exist : ' . $image);
        }

        $client = $this->client();
        $youtube = $this->youtube;

        // Check to ensure that the access token was successfully acquired.
        if ($client->getAccessToken()) {
            try {
                // Specify the size of each chunk of data, in bytes. Set a higher value for
                // reliable connection as fewer chunks lead to faster uploads. Set a lower
                // value for better recovery on less reliable connections.
                $chunkSizeBytes = 1 * 1024 * 1024;

                // Setting the defer flag to true tells the client to return a request which can be called
                // with ->execute(); instead of making the API call immediately.
                $client->setDefer(true);

                // Create a request for the API's thumbnails.set method to upload the image and associate
                // it with the appropriate video.
                $setRequest = $youtube->thumbnails->set($video_id);

                // Create a MediaFileUpload object for resumable uploads.
                $media = new Google_Http_MediaFileUpload(
                    $client,
                    $setRequest,
                    'image/png',
                    null,
                    true,
                    $chunkSizeBytes
                );
                $media->setFileSize(filesize($image));


                // Read the media file and upload it chunk by chunk.
                $status = false;
                $handle = fopen($image, "rb");
                while (!$status && !feof($handle)) {
                    $chunk = fread($handle, $chunkSizeBytes);
                    $status = $media->nextChunk($chunk);
                }

                fclose($handle);

                // If you want to make other calls after the file upload, set setDefer back to false
                $client->setDefer(false);
            } catch (Google_Service_Exception $e) {
                throw new Exception($e->getMessage());
            } catch (Google_Exception $e) {
                throw new Exception($e->getMessage());
            }

            $_SESSION[$this->tokenSessionKey] = $client->getAccessToken();
        } else {
            throw new Exception('You are not authorized to do that');
        }

        return $this;
    }

    /**
     * Update banner
     * @params string $image
     *
     * @return void
     */
    public function withThumbnail($image)
    {
        return $this->updateThumbnail($this->uploaded_video_id, $image);
    }

    /**
     * Delete video by the given id
     * @params video_id
     *
     * @return void
     */
    public function deleteVideo($video_id)
    {
        $client = $this->client();
        $youtube = $this->youtube;

        // Check to ensure that the access token was successfully acquired.
        if ($client->getAccessToken()) {
            try {
                $youtube->videos->delete($video_id);
            } catch (Google_Service_Exception $e) {
                throw new Exception($e->getMessage());
            } catch (Google_Exception $e) {
                throw new Exception($e->getMessage());
            }

            $_SESSION[$this->tokenSessionKey] = $client->getAccessToken();
        } else {
            throw new Exception('You are not authorized to do that');
        }

        return $this;
    }
}
