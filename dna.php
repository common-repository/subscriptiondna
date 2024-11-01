<?php
/*
  Plugin Name: SubscriptionDNA
  Plugin URI: http://SubscriptionDNA.com/wordpress/
  Description: Quickly integrate your website with your SubscriptionDNA Enterprise Subscription Billing and Members Management Platform account.
  Version: 2.0
  Author: SubscriptionDNA.com
  Author URI: http://SubscriptionDNA.com/
 */

/*
  Initialize
 */

$GLOBALS['SubscriptionDNA'] = Array();

// Common Plugin Functions

/**
 * Initializes global variables used in plugin like DNA Front-End pages list,TLD, and api key
 *
 */
function SubscriptionDNA_Initialize()
{

    $GLOBALS['SubscriptionDNA']['Settings'] = SubscriptionDNA_Get_Settings();
    return TRUE;
}

/**
 * returns DNA settings from wordpress db like page menu,TLD and API Key
 *
 */
function SubscriptionDNA_Get_Settings()
{

    $Settings = get_option('SubscriptionDNA_Settings');

    return $Settings;
}
function SubscriptionDNA_DownloadUrl($tld, $api_key, $path)
{
    $ch = curl_init();    // initialize curl handle
    curl_setopt($ch, CURLOPT_URL, "https://subscriptiondna.com/plugins/subscriptiondna.zip"); // set url to post to
    curl_setopt($ch, CURLOPT_FAILONERROR, 1);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); // return into a variable
    curl_setopt($ch, CURLOPT_TIMEOUT, 300); // times out after 4s
    curl_setopt($ch, CURLOPT_POST, TRUE);
    curl_setopt($ch, CURLOPT_POSTFIELDS, "tld=" . $tld . "&api_key=" . $api_key);
    $data = curl_exec($ch); // run the whole process
    curl_close($ch);
    if ($data == "" || $data == "denied")
    {
        return("Please Enter a Valid Subscription DNA Account TLD and API Key");
    }
    $fp = fopen($path, "w");
    if ($fp)
    {
        fwrite($fp, $data);
        fclose($fp);
    }
    else
    {
        return("Activation failed: Permissions denied.");
    }
}
/**
 *  Display SubscriptionDNA link in wp menu
 *
 */
function SubscriptionDNA_admin_menu()
{
    add_menu_page('SubscriptionDNA', 'SubscriptionDNA', 0, __FILE__, 'SubscriptionDNA_Options_Edit');
    add_submenu_page(__FILE__, __('Settings', 'SubscriptionDNA'), __('Configuration', 'SubscriptionDNA'), 10, __FILE__, 'SubscriptionDNA_Options_Edit');
    return TRUE;
}

/**
 *  Displays form to edit DNA settings like API Key, TLD and page SSL settings
 *
 */
function SubscriptionDNA_Options_Edit()
{
    if (!empty($_POST['action']) AND 'update' == $_POST['action'])
    {
        if ($_POST["SubscriptionDNA_Settings"]["TLD"] == "")
        {
            $message="Please Enter Subscription DNA<sup>®</sup> Account TLD.";
        }
        else if ($_POST["SubscriptionDNA_Settings"]["API_KEY"] == "")
        {
            $message="Please Enter a Valid API Key.";
        }
        else
        {
            ini_set('memory_limit', '256M');
            $path = wp_upload_dir();
            if ($path["error"] == "")
            {
                $path = $path["path"] . "/subscriptiondna.zip";
                $error = SubscriptionDNA_DownloadUrl($_POST["SubscriptionDNA_Settings"]["TLD"], $_POST["SubscriptionDNA_Settings"]["API_KEY"], $path);
            }
            else
            {
                $error = $path["error"];
            }
            if ($error == "")
            {
                $zip = zip_open($path);
                if ($zip)
                {
                    $reading = false;
                    while ($zip_entry = zip_read($zip))
                    {
                        $reading = true;
                        if (zip_entry_open($zip, $zip_entry, "r"))
                        {
                            echo "Installing : " . zip_entry_name($zip_entry) . "<br />";
                            $data = zip_entry_read($zip_entry, zip_entry_filesize($zip_entry));
                            if (zip_entry_filesize($zip_entry) > 0)
                            {
                                $fp = fopen(dirname(__FILE__) . "/../" . zip_entry_name($zip_entry), "w");
                                if ($fp)
                                {
                                    fwrite($fp, $data);
                                    fclose($fp);
                                }
                                else
                                {
                                    $error = "Permisions error while activating plugin.";
                                }
                            }
                            else
                            {
                                @mkdir(dirname(__FILE__) . "/../" . zip_entry_name($zip_entry));
                            }
                            zip_entry_close($zip_entry);
                        }
                        else
                        {
                            echo "Error reading: " . print_r($zip_entry) . "<br />";
                        }
                    }
                    if (!$reading)
                    {
                        $error = "Error reading: " . $path . "<br />";
                    }
                    zip_close($zip);
                    if ($error == "")
                    {
                        ?>
                        <script>
                            location.href = 'admin.php?page=subscriptiondna%2Fdna.php&SubscriptionDNA_Upgraded=1';
                        </script>
                        <?php
                    }
                }
                else
                {
                    $error = "zip_open is disabled on the server.";
                }
            }
            @unlink($path);
            $message=$error;
        }
        $Aarzi = SubscriptionDNA_Options_Save();
        
    }

    $logo_no_margin = "1";
    include("dna_header.php");
    ?>	
    <div class="wrap">
        <div id="icon-edit" class="icon32"><br /></div>
        <h2><?php echo __('Activation'); ?></h2>
        <div>
<b>Thanks for installing the Subscription DNA<sup>&reg;</sup> plugin for WordPress!</b><br /><br />
This plugin greatly helps speed up front-end development integration tying your <b>existing</b> Subscription DNA® account. To begin, simply activate the plugin below by entering your Subscription DNA® account name (TLD) and the unique API Key found in your DNA account's configuration screen.<br><br>
If you're simply checking out what this plugin does and do not yet have a Subscription DNA® account, activation is required. You will first need to contact us to share a little information about your business model and to request a free 30 day sandbox. Please visit <a href="https://SubscriptionDNA.com/contact/" target="_blank">https://SubscriptionDNA.com/contact/</a> today to email or call 513-574-9800 for more information.<br><br>
Subscription DNA® is a billing, paywall and member platform that provides an amazing suite of automation tools.  With costs that start as low as $99/month (+ setup), any startup business can afford the low risk of investment while gaining access to a professional enterprise platform. We're intelligently priced to work for both new and existing growing businesses with any size subscriber base. We serve a wide range of business models and can customize our platform just for you. Call us and let's brainstorm!


        </div><br />
        <div id="message" class="error-message fade">
            <p><strong><?php echo __($message); ?></strong></p>
        </div>

        <form action="admin.php?page=<?php echo $_GET['page']; ?>" method="post">
            <fieldset class="options">
                <legend></legend>
                <div id="poststuff" class="metabox-holder has-right-sidebar">
                    <div id="tagsdiv-post_tag" class="postbox">
                        <h3 class='hndle'><span>Subscription DNA<sup>&reg;</sup> Account Information</span></h3>

                        <div style="padding: 25px;">
                            <b>Account Name (TLD):</b><br />
                            <input type="text" name="SubscriptionDNA_Settings[TLD]" value="<?php echo($GLOBALS['SubscriptionDNA']['Settings']['TLD']); ?>" style="width:300px;" /><br />
                            (Your TLD is the account name setup within your Subscription DNA<sup>&reg;</sup> console.  For example, https://demo.xsubscribe.com, then "demo" is your TLD)

                            <p>
                                <b>API Key:</b><br />
                                <input  type="text" name="SubscriptionDNA_Settings[API_KEY]" value="<?php echo($GLOBALS['SubscriptionDNA']['Settings']['API_KEY']); ?>" style="width:300px;" /><br />
                                (Your API Key is found on the Configurations page within your Subscription DNA<sup>&reg;</sup> console).
                            </p>

                            <br>

                        </div>

                    </div>
                </div>
            </fieldset>

            <input type="hidden" name="action" value="update"                                 />
            <input type="submit" name="submit" class="button-secondary action" value="<?php echo __('Activate Plugin &raquo;'); ?>"/>
        </form>
        <?php
        ?>
    </div>
    <?php
    return TRUE;
}

/**
 *  Saves DNA settings like API Key, TLD and page SSL settings
 *
 */
function SubscriptionDNA_Options_Save()
{
    update_option('SubscriptionDNA_Settings', $_POST['SubscriptionDNA_Settings']);
    $GLOBALS['SubscriptionDNA']['Settings'] = SubscriptionDNA_Get_Settings();
    return TRUE;
}

/*
  Misc. Plugin Setup Code
 */


if (function_exists('add_action'))
{
    $Aarzi = add_action('init', 'SubscriptionDNA_Initialize');
}


if (function_exists('add_action'))
{

    $Aarzi = add_action('admin_menu', 'SubscriptionDNA_admin_menu');
}
?>