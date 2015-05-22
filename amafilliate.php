<?php
/**
 * @package Amafilliate
 * @version 0.5
 */
/*
Plugin Name: Amafilliate
Plugin URI: https://github.com/greghesp/amaffiliate
Description: Manually control your Amazon affiliate links between the USA and UK
Author: Greg Hesp
Version: 0.5
Author URI: http://greghesp.com
*/

defined( 'ABSPATH' ) or die( 'Plugin file cannot be accessed directly.' );
 
add_action( 'admin_menu', 'wpaa_add_admin_menu' );
add_action( 'admin_init', 'wpaa_settings_init' );
add_action('init','find_location');
add_action('the_post','everypost_func');


function wpaa_add_admin_menu(  ) { 

    add_options_page( 'Amaffiliate', 'Amaffiliate', 'manage_options', 'amaffiliate', 'amaffiliate_options_page' );

}


function wpaa_settings_init(  ) { 

    register_setting( 'pluginPage', 'wpaa_settings' );

    add_settings_section(
        'wpaa_pluginPage_section', 
        __( '', 'wordpress' ), 
        'wpaa_settings_section_callback', 
        'pluginPage'
    );

    add_settings_field( 
        'wpaa_public_key', 
        __( 'Amazon Public Key', 'wordpress' ), 
        'wpaa_public_key_render', 
        'pluginPage', 
        'wpaa_pluginPage_section' 
    );

    add_settings_field( 
        'wpaa_private_key', 
        __( 'Amazon Private Key', 'wordpress' ), 
        'wpaa_private_key_render', 
        'pluginPage', 
        'wpaa_pluginPage_section' 
    );

    add_settings_field( 
        'wpaa_affiliate_id_uk', 
        __( 'Amazon.co.uk Affiliate ID', 'wordpress' ), 
        'wpaa_affiliate_id_uk_render', 
        'pluginPage', 
        'wpaa_pluginPage_section' 
    );

    add_settings_field( 
        'wpaa_affiliate_id_us', 
        __( 'Amazon.com Affiliate ID', 'wordpress' ), 
        'wpaa_affiliate_id_us_render', 
        'pluginPage', 
        'wpaa_pluginPage_section' 
    );


}


function wpaa_public_key_render(  ) { 

    $options = get_option( 'wpaa_settings' );
    ?>
    <input type='text' name='wpaa_settings[wpaa_public_key]' value='<?php echo $options['wpaa_public_key']; ?>'>
    <?php

}


function wpaa_private_key_render(  ) { 

    $options = get_option( 'wpaa_settings' );
    ?>
    <input type='text' name='wpaa_settings[wpaa_private_key]' value='<?php echo $options['wpaa_private_key']; ?>'>
    <?php

}


function wpaa_affiliate_id_uk_render(  ) { 

    $options = get_option( 'wpaa_settings' );
    ?>
    <input type='text' name='wpaa_settings[wpaa_affiliate_id_uk]' value='<?php echo $options['wpaa_affiliate_id_uk']; ?>'>
    <?php

}


function wpaa_affiliate_id_us_render(  ) { 

    $options = get_option( 'wpaa_settings' );
    ?>
    <input type='text' name='wpaa_settings[wpaa_affiliate_id_us]' value='<?php echo $options['wpaa_affiliate_id_us']; ?>'>
    <?php

}


function wpaa_settings_section_callback(  ) { 

    echo __( 'Enter your Amazon Affiliate settings below to get started.
 
    <h3>How to use Amafilliate</h3>
    <p>If your product has a generic ASIN, use the custom field \'generic-asin\'.<br/>
    If your product has a UK specific ASIN, use the custom field \'uk-asin\'<br/>
    If your product has a US specific ASIN, use the custom field \'us-asin\'</br>
    If you want to link to a site other than Amazon, use the custom field \'other-link\'</p>
 
    <p>To call the price, wishlist and Amazon link, use the following variables in your theme: <br/>
        $amaffiliate_price for the Price<br/>
        $amaffiliate_url for the Amazon URL<br/>
        $amaffiliate_wishlist for the Amazon Wishlist</p>', 'wordpress' );

}


function amaffiliate_options_page(  ) { 

    ?>
    <form action='options.php' method='post'>
        
        <h2>Amaffiliate Settings</h2>
        
        <?php
        settings_fields( 'pluginPage' );
        do_settings_sections( 'pluginPage' );
        submit_button();
        ?>
        
    </form>
    <?php

}

 
function find_location() {
   
    if (isset($_COOKIE['countrycode']))
        { }
    else 
    {
 
        $userip = $_SERVER['REMOTE_ADDR'];
        $url = 'https://freegeoip.net/json/'.$userip;
        $json = file_get_contents($url);
        $obj = json_decode($json);
        global $countrycode;
        $countrycode = $obj->country_code;
        $ip = $obj->ip;
        $date_of_expiry = time() + 2628000 ;

        setcookie( "countrycode", $countrycode, $date_of_expiry ); 
        return $countrycode;
    }
}
 
function aws_signed_request($region, $params, $public_key, $private_key, $associate_tag=NULL, $version='2011-08-01')
{
    // some paramters
    $method = 'GET';
    $host = 'webservices.amazon.'.$region;
    $uri = '/onca/xml';
   
    // additional parameters
    $params['Service'] = 'AWSECommerceService';
    $params['AWSAccessKeyId'] = $public_key;
    // GMT timestamp
    $params['Timestamp'] = gmdate('Y-m-d\TH:i:s\Z');
    // API version
    $params['Version'] = $version;
    if ($associate_tag !== NULL) {
        $params['AssociateTag'] = $associate_tag;
    }
   
    // sort the parameters
    ksort($params);
   
    // create the canonicalized query
    $canonicalized_query = array();
    foreach ($params as $param=>$value)
    {
        $param = str_replace('%7E', '~', rawurlencode($param));
        $value = str_replace('%7E', '~', rawurlencode($value));
        $canonicalized_query[] = $param.'='.$value;
    }
    $canonicalized_query = implode('&', $canonicalized_query);
   
    // create the string to sign
    $string_to_sign = $method."\n".$host."\n".$uri."\n".$canonicalized_query;
   
    // calculate HMAC with SHA256 and base64-encoding
    $signature = base64_encode(hash_hmac('sha256', $string_to_sign, $private_key, TRUE));
   
    // encode the signature for the request
    $signature = str_replace('%7E', '~', rawurlencode($signature));
   
    // create request
    $request = 'http://'.$host.$uri.'?'.$canonicalized_query.'&Signature='.$signature;
   
    return $request;
}
 
function get_aws_details($region,$itemid)
{
    $options = get_option( 'wpaa_settings' );

 
    $public_key = $options['wpaa_public_key'];
    $private_key = $options['wpaa_private_key'];
    $associate_tag_uk = $options['wpaa_affiliate_id_uk'];
    $associate_tag_us = $options['wpaa_affiliate_id_us'];
 
    if($region == "co.uk")
       {
        $associate_tag = $associate_tag_uk;
       }
    else
      {
        $associate_tag = $associate_tag_us;
      }
 
    $request = aws_signed_request($region, array(
        'Operation' => 'ItemLookup',
        'ItemId' => $itemid,
        'ResponseGroup' => 'Offers,EditorialReview,ItemAttributes'), $public_key, $private_key, $associate_tag);
 
    $response = @file_get_contents($request);
    if ($response === FALSE) {
        echo "Request failed.\n";
    } else {
        // parse XML
        $pxml = simplexml_load_string($response);
 
    //print_r($pxml);
    if ($pxml === FALSE) {
        echo "Response could not be parsed.\n";
    } else {
        if (isset($pxml->Items->Item->Offers->Offer->OfferListing->Price->FormattedPrice)) {
            $formatprice = $pxml->Items->Item->Offers->Offer->OfferListing->Price->FormattedPrice;
            $wishlisturl = $pxml->Items->Item->ItemLinks->ItemLink[0]->URL;
            $producturl = $pxml->Items->Item->DetailPageURL;
        }
    }
    }
return array($formatprice, $producturl, $wishlisturl);
}
 
function everypost_func($obj){
    //global $testvar;  
    global $countrycode;

    if (is_null($countrycode))
    {
        $countrycode = find_location();
    }
     
    
 
        if (get_post_meta(get_the_ID(), 'other-link', true))
        {
                $url = get_post_meta(get_the_ID(), 'other-link', true);
                $status = 200;
                $price = get_post_meta(get_the_ID(), 'other-price', true);
                //echo "Looks like you've got other-link selected";
        }
        elseif (get_post_meta(get_the_ID(), 'generic-asin', true))
        {
                $status = 100;
                $asin = get_post_meta(get_the_ID(), 'generic-asin', true);
                //echo "Looks like you've got a generic-asin";
     
                if($_COOKIE['countrycode'] == "GB" || $countrycode == "GB")
                {
                        $reg = "co.uk";
                        //echo "and we've detected you're in the UK <br>";
                }
                else
                {
                        $reg = "com";
                        //echo "and we've detected you're not from the UK <br>";
                }
        }
        else
        {
                if(($_COOKIE['countrycode'] == "GB" || $countrycode == "GB") && get_post_meta(get_the_ID(), 'uk-asin', true))
                {
                        $asin =  get_post_meta(get_the_ID(), 'uk-asin', true);
                        $reg = "co.uk";
                        $status = 100;
                        //echo "looks like you're in the UK, and we have a UK link<br>";
                }
                elseif (get_post_meta(get_the_ID(), 'us-asin', true))
                {
                        $asin =  get_post_meta(get_the_ID(), 'us-asin', true);
                        $reg = "com";
                        $status = 100;
                        //echo "looks like you're not in the UK, but we have a US link for you<br>";
                }
                elseif (get_post_meta(get_the_ID(), 'uk-asin', true)) 
                {
                        $asin =  get_post_meta(get_the_ID(), 'uk-asin', true);
                        $reg = "co.uk";
                        $status = 100;
                        //echo "looks like you're not the UK, but we have a UK link<br>";
                }      
                else
                {
                        $status = 404;        
                        //echo "looks like nothing is here for you<br>";      
                }
        }
     
        if($status == 100)
        {
                global $amaffiliate_price;
                global $amaffiliate_url;
                global $amaffiliate_wishlist;
                $results = get_aws_details($reg, $asin);
                $amaffiliate_price = $results[0][0];              
                $amaffiliate_url = $results[1][0];
                $amaffiliate_wishlist = $results[2][0];
        }
        elseif($status == 404)
        {
                global $amaffiliate_price;
                global $amaffiliate_url;
                global $amaffiliate_wishlist;
                $amaffiliate_price = "More Info";
                $amaffiliate_url = get_the_permalink();
                $amaffiliate_wishlist = NULL;
        }
        elseif($status == 200)
        {
                global $amaffiliate_price;
                global $amaffiliate_url;
                global $amaffiliate_wishlist;
                $amaffiliate_price = $price;
                $amaffiliate_url = $url;
                $amaffiliate_wishlist = NULL;
        }
    
}


?>