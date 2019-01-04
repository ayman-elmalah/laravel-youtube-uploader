# Laravel Youtube Uploader
laravel youtube uploader is a php package written by [Ayman Elmalah](https://github.com/ayman-elmalah) with laravel to handle many youtube sdk functionality by making it's api more easy . 

## Features
- Uploading videos to user channel
- Creating playlists
- Insert video to existing playlist
- Set thumbnail to existing video or at uploading video
- Deleting video

# Installation Guide
At laravel project install package using composer
```
composer require ayman-elmalah/laravel-youtube-uploader
```
The package is compatible with laravel 5.7 so you don't need to set providers or aliases for the package, we're using laravel auto discovery

## Get Your Credentials From Google
 - Go to [Google Developers Console](https://console.developers.google.com/) Press Credentials from the sidebar then create project from OAuth consent screen then Click Credentials => Create credentials => OAuth client id, choose it web application and set you  Authorized redirect URIs, also you can edit or add new urls later
 - You will get Client Id and Client Secret
 - Go to your .env file and paste your credentials to be like this

 ```
 GOOGLE_CLIENT_ID=YOUR_CLIENT_ID
 GOOGLE_CLIENT_SECRET=YOUR_SECRET
 ```
 
 You are now ready to use the package
 
 There is two steps to do any thing for the package, get authenticated url and do the youtube api action
 
 ### To get the auth url, just go to routes/web.php and do this route or the path you want to use
 ```
 Route::get('youtube/auth', 'YoutubeUploaderController@auth');
 ```
 At the controller, you will get the url and you can show it in view file or redirect user to it directly
 ```
 use Youtube;
 public function auth() {
     $redirect_url ='http://localhost/youtube-uploader/public/youtube/callback';  // Upload video path
 
     return redirect(Youtube::setRedirectUrl($redirect_url)->AuthUrl());
   }
 ```
 This code will authenticate user then redirect user to another url to do your logic on it, and to do any logic code, you need to add route url to routes/web.php
  ```
  Route::get('youtube/callback', 'YoutubeUploaderController@callback');
  ```
  
  Don't forget to save full path of callback url at google console developer at credential for project
  
  The we will now show our logic of the package
  
  ## Upload video
  ```
  public function callback(Request $request) {
        $redirect_url ='http://localhost/youtube-uploader/public/youtube/callback';
        $video = public_path('VIDEO_FILE');
        $image = public_path('IMAGE_FILE');
      	$youtube = Youtube::setRedirectUrl($redirect_url)->upload($video,
              [
                  'title' => 'TITLE',
                  'description' => 'DESCRIPTION',
                  'tags' => ['tag 1', 'tag 2'],
                  'category_id' => '22',
              ]
          );
  
        // Get uploaded video id
        $video_id = $youtube->uploadedVideoId();
  }
  ```
  
  ## Set thumbnail to existing video
  Remember that you need to set auth url at routes for each callback url
  ```
  public function another_callback(Request $request) {
      $redirect_url ='http://localhost/youtube-uploader/public/youtube/callback/another_callback';
      $video_id = 'VIDEO_ID';
      $image = public_path('THUMBNAIL_PATH');
      $youtube = Youtube::setRedirectUrl($redirect_url)->updateThumbnail($video_id, $image);
  }
  ```
  
  ## Set thumbnail at uploading
  Remember that you need to set auth url at routes for each callback url  
  ```
  public function another_callback(Request $request) {
        $redirect_url ='http://localhost/youtube-uploader/public/youtube/callback/another_callback';
        $video = public_path('VIDEO_PATH');
        $image = public_path('THUMBNAIL_PATH');
        $youtube = Youtube::setRedirectUrl($redirect_url)->upload($video,
               [
                   'title' => 'TITLE',
                   'description' => 'Description1',
                   'tags' => ['tag 1', 'tag2'],
                   'category_id' => '22',
               ]
           )->withThumbnail($image);
    
           $video_id = $youtube->uploadedVideoId();
  }
  ```
  
  ## Set tags for existing video
  Remember that you need to set auth url at routes for each callback url
   ```
   public function another_callback(Request $request) {
          $redirect_url ='http://localhost/youtube-uploader/public/youtube/callback/another_callback';
          $video_id = 'YOUR_VIDEO_ID';
          $youtube = Youtube::setRedirectUrl($redirect_url)->updateTags($video_id, ['tag 1', 'tag2', 'Tag3', 'Tag 4']);
   }
   ```
   
   ## Create Playlist
   Remember that you need to set auth url at routes for each callback url
   
   ```
    public function another_callback(Request $request) {
          $redirect_url ='http://localhost/youtube-uploader/public/youtube/callback/another_callback';
          $youtube = Youtube::setRedirectUrl($redirect_url)
                            ->createPlaylist('TITLE', 'DESCRIPTION');
    
          // Get playlist id
          $playlist_id = $youtube->createdPlaylistId();
    }
   ```
   
   ## Insert Video to playlist
   Remember that you need to set auth url at routes for each callback url
   ```
    public function another_callback(Request $request) {
        $redirect_url ='http://localhost/youtube-uploader/public/youtube/callback/another_callback';
        $youtube = Youtube::setRedirectUrl($redirect_url)
                          ->VideoToPlaylist('VIDEO_ID', 'PLAYLIST_ID');    
    }
   ```
   
   ## Delete video by the given id
   Remember that you need to set auth url at routes for each callback url
   ```
   public function another_callback(Request $request) {
       $redirect_url ='http://localhost/youtube-uploader/public/youtube/callback/another_callback';
       $video_id = 'VIDEO_ID';
       $youtube = Youtube::setRedirectUrl($redirect_url)->deleteVideo($video_id);
   }
   ```
   
   # If you have any question, issue Or request, i'll be happy if hear any thing from you
   