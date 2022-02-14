<?php

namespace App\Library;

use AmazonPHP\SellingPartner\Configuration;
use Buzz\Client\Curl;
use Nyholm\Psr7\Factory\Psr17Factory;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use AmazonPHP\SellingPartner\SellingPartnerSDK;
use AmazonPHP\SellingPartner\AccessToken;
use AmazonPHP\SellingPartner\Regions;
use AmazonPHP\SellingPartner\Marketplace;
use AmazonPHP\SellingPartner\Model\Feeds\CreateFeedDocumentSpecification;
use AmazonPHP\SellingPartner\Model\Feeds\CreateFeedSpecification;
use Config;
use Exception;

class AmazonSP {

  /* This static field maintains the instances of singleton */
  private static $instance = [];

  /* Stores the API token with null or instance of \AmazonPHP\SellingPartner\AccessToken */
  private AccessToken $accessToken;

  /* Access the Amazon sdk */
  private SellingPartnerSDK $sdk;

  /**
   * This should be private to prevent direct access and multiple creation of instance
   */
  protected function __construct()
  {
    $this->generateToken();
  }

  /**
   * This is the static method that controls the access to the singleton
   * instance. On the first run, it creates a singleton object and places it
   * into the static field. On subsequent runs, it returns the client existing
   * object stored in the static field.
   *
   * This implementation lets you subclass the Singleton class while keeping
   * just one instance of each subclass around.
   */
  public static function getInstance()
  {
    $cls = static::class;
    if(!isset(self::$instance[$cls])) {
      self::$instance[$cls] = new Static();
    }
    return self::$instance[$cls];
  }

  /**
   * This class should not be cloneable.
   */
  protected function __clone() {}

  /**
   * This Class should not be restorable from strings.
   */
  public function __wakeup()
  {
    throw new Exception("Cannot unserialize a singleton.");
  }

  
  /**
   * Generates the Refereh token based on LWA auth
   * @param void
   * @return void
   */
  private function generateToken(): void
  {
    $credentials = Config::get('amazon-mws')['spapi'];

    /* Required Variables */
    $factory = new Psr17Factory();
    $client = new Curl($factory);

    /* !!! NOTE: CHANGE THE PATH HERE !!! */
    $logger = new Logger('name');
    $logger->pushHandler(new StreamHandler(__DIR__ . '/sp-api-php.log', Logger::DEBUG));

    $configuration = Configuration::forIAMUser($credentials['lwaClientId'], $credentials['lwaClientIdSecret'], $credentials['awsAccessKey'], $credentials['awsSecretKey']);
    
    $this->sdk = SellingPartnerSDK::create($client, $factory, $factory, $configuration, $logger);
    $this->accessToken = $this->sdk->oAuth()->exchangeRefreshToken($credentials['seller_oauth_refresh_token']);
  }

  /**
   * Send Feed to marketplace
   * @param string $path -- filepath
   * @param string $feedType -- feedtype
   * @param array
   */
  public function send_feed(string $path, string $feedType = "_POST_FLAT_FILE_PRICEANDQUANTITYONLY_UPDATE_DATA_"): array
  {
    // $path = storage_path("app/marketplace/P20220125231713.txt");
    $data = new CreateFeedDocumentSpecification(["content_type" => file_get_contents($path)]);

    $feedDocument = $this->sdk->feeds()->createFeedDocument($this->accessToken, Regions::NORTH_AMERICA, $data);
    $feed = new CreateFeedSpecification(['feed_type' => $feedType, 'marketplace_ids' => Marketplace::US()->id(), 'input_feed_document_id' => $feedDocument->getFeedDocumentId()]);
    $feedResponse = $this->sdk->feeds()->createFeed($this->accessToken, Regions::NORTH_AMERICA, $feed)::getters();
    return $feedResponse;
  }

  /**
   * To get the feed result
   * @param string $feedId
   * @return array
   */
  public function get_feed_result(string $feedId): array
  {
    $feedResponse = $this->sdk->feeds()->getFeed($this->accessToken, Regions::NORTH_AMERICA, $feedId);
    return $feedResponse::getters();
  }
}
