<?php

namespace AymanElmalah\YoutubeUploader;

use Illuminate\Support\ServiceProvider;

class YoutubeUploaderServiceProvider extends ServiceProvider {
  /**
   * Boot the service provider.
   *
   * @return void
   */
  public function boot() {
    
  }

  /**
   * Register the service provider.
   *
   * @return void
   */
  public function register() {
      $this->app->bind(Youtube::class, function($app) {
          return new Youtube();
      });

      $this->app->alias(Youtube::class, 'youtube');
  }
}
