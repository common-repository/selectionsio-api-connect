<?php
/*
* Plugin Name:selectionsio API-Connect
* Plugin URI: https://selectionsio.de/api-einstellungen
* Description: Mit dieser App können Sie Selectionsio Kalkulationen und Produktkonfiguratoren direkt in Ihre WordPress-Website einbinden. With this app you can embed Selectionsio calculations and product configurators directly into your WordPress website.
* Version: 1.0.0
* Author: Selectionsio GmbH
* Author URI: https://selectionsio.de
* License: GPL2
*/
include_once 'shortcodes.php';
// Erstelle neuen Menüpunkt im Backend
function Selectionsioapi_settings_menu() {
    add_menu_page( 'S/IO API-Connect', 'S/IO API-Connect', 'manage_options', 'Selectionsioapi-settings', 'Selectionsioapi_settings_page', 'dashicons-rest-api' );
}
add_action( 'admin_menu', 'Selectionsioapi_settings_menu' );

// Erstelle Seite für API Einstellungen
function Selectionsioapi_settings_page() {
    ?>
<style>
.SIO-logo {
    position: absolute;
    right: 20px;
    top: 50%;
    margin-top: -60px;
    width: 313px;
    height: 80px;
    background: url(<?php echo plugins_url( 'images/selectionsio.png', __FILE__ ); ?>) center top/313px 63px no-repeat;
}
.SIO-version {
    position: absolute;
    width: 100%;
    bottom: 0;
    text-align: center;
    color: #72777c;
    line-height: 1em;
}
#textarea {
    width: 400px;
}
</style>

<div class="wrap">
    <a target="_blank" href="https://Selectionsio.de/"><div class="SIO-logo">
			<div class="SIO-version">Selectionsio Produkt-Konfigurator v.1.0.0</div>
		</div></a>
        <h1>Selectionsio Produkt-Konfigurator</h1>
        <form method="post" action="options.php" id="thisform">
            <?php
                settings_fields( 'Selectionsioapi-settings-group' );
                do_settings_sections( 'Selectionsioapi-settings-group' );
            ?>
            <table class="form-table">
                <tr valign="top">
                    <th scope="row">SIO URL</th>
                    <td><input type="text" id="sio_api_link" name="sio_api_link" value="<?php echo esc_attr( get_option('sio_api_link') ); ?>" /></td>
                </tr>
                <tr valign="top">
                    <th scope="row">Shop UUID</th>
                    <td><input type="text" id="sio_shop_uuid" name="sio_shop_uuid" value="<?php echo esc_attr( get_option('sio_shop_uuid') ); ?>" /></td>
                </tr>
                <!--<tr valign="top">
                    <th scope="row">API Token</th>
                    <td><input type="text" name="sio_api_token" value="<?php echo esc_attr( get_option('sio_api_token') ); ?>" /></td>
                </tr>-->
                <tr valign="top">
                    <th scope="row">Preis Ausgabe:</th>
                    <td>
                    <select name="sio_api_price" id="sio_api_price">
                            <option value="1" <?php if(get_option('sio_api_price') == '1') echo 'selected'; ?>>Preis anzeigen</option>
                            <option value="1" <?php if(get_option('sio_api_price') == '2') echo 'selected'; ?>>Wert anzeigen</option>
                            <option value="0" <?php if(get_option('sio_api_price') == '0') echo 'selected'; ?>>Preis & Wert nicht anzeigen</option>
                    </select>
                    </tr>
                    <tr valign="top">
                        <th scope="row">Maßeinheit</th>
                        <td><input type="text" id="sio_shop_einheit" name="sio_shop_einheit" value="<?php echo esc_attr( get_option('sio_shop_einheit') ); ?>" /></td>
                    </tr>
                    <tr valign="top">
                    <th scope="row">Bestellenbutton:</th>
                    <td>
                    <select name="sio_api_salebutton" id="sio_api_salebutton">
                            <option value="1" <?php if(get_option('sio_api_salebutton') == '1') echo 'selected'; ?>>Bestellenbutton anzeigen</option>
                            <option value="0" <?php if(get_option('sio_api_salebutton') == '0') echo 'selected'; ?>>Bestellenbutton nicht anzeigen</option>
                    </select>
                    </tr>
                 <tr valign="top">
                    <th scope="row">Produktgruppe</th>
                    <td>
                        <select name="sio_api_prgroup" id="sio_api_prgroup"></select>
                    </td>
                 </tr>
                 <tr valign="top">
                    <th scope="row">Produkte</th>
                    <td>
                        <select name="sio_products" id="sio_products"></select>
                    </td>
                 </tr>
                 <tr valign="top" id="sio_product_uuid_field" style="display: none;">
                <th scope="row">Product UUID</th>
                <td><input type="text" name="sio_product_uuid" id="sio_product_uuid" value="" /></td>
            </tr>
        </table>
        <?php submit_button(); ?>
    </form>
</div>
<script>
    var calcHasError = false;
    var $ = jQuery.noConflict();
    var url = document.getElementById('sio_api_link');
    var shopUUID = document.getElementById('sio_shop_uuid');
    var apiPrgroup = document.getElementById('sio_api_prgroup');
    var productUuidField = document.getElementById('sio_product_uuid_field');
    var apiPriceSelect = document.getElementById('sio_api_price');
    var apiSaleSelect = document.getElementById('sio_api_salebutton');
    var products = document.getElementById('sio_products');
    var productUuid = document.getElementById('sio_product_uuid');
    var measure = document.getElementById('sio_shop_einheit');
    var thisform = document.getElementById('thisform');
    var selectedgroup = "<?php echo esc_attr( get_option('sio_api_prgroup') ); ?>";
    var selectedproduct = "<?php echo esc_attr( get_option('sio_products') ); ?>";
    var textareaField = document.getElementById('textarea');

function Selectionsio_loadProducts() {
		$('#sio_products').empty();

		$.ajax({
			url: url.value +  "/apps/api/product/getallbyproductgroup/" + $('#sio_api_prgroup').val(),
			contentType: "application/json",
			method: 'GET',
			success: function(result) {
				$.each(result.data, function(index, value) {
					Selectionsio_appendProduct(value);
				});

				//loadCalc();
			}
		})
	}
    function Selectionsio_appendProduct(data, depth = '') {
        if(data.uuid == selectedproduct) {
            var selectfildpr = "selected";
        }
		$('#sio_products').append('<option value="' + data.uuid + '" ' + selectfildpr + '>' + depth + data.title + '</option>');
        setSelectionsioProductUuid();
	}
function setSelectionsioProductUuid() {
    productUuid.value = products.value;

const shortcodeOptions = {
    calcid: products.value,
    price: apiPriceSelect.value === '1' ? 1 : 0,
    salebtn: apiSaleSelect.value === '1' ? 1 : 0,
    measure: measure.value || ''
};

let shortcodeString = '[Selectionsioproduct_calculator';

for (const key in shortcodeOptions) {
    if (shortcodeOptions[key]) {
        shortcodeString += ` ${key}="${shortcodeOptions[key]}"`;
    }
}
shortcodeString += ']';

productUuidField.style.display = 'table-row';
shortcode.style.display = 'block';
shortcode.innerHTML = `Bitte mit dem Folgenden Shortcode einbinden: ${shortcodeString}`;

document.getElementById('textarea').value = `${shortcodeString}`;
}
function Selectionsio_loadProductGroups() {
		$.ajax({
			url: url.value +  "/apps/api/productgroup/gettree/" + shopUUID.value,
			contentType: "application/json",
			method: 'GET',
			success: function(result) {
				$.each(result.data, function(index, value) {
					Selectionsio_appendProductGroup(value);
				});
			}
		})
	}

	function Selectionsio_appendProductGroup(data, depth = '') {
        if(data.uuid == selectedgroup) {
            var selectfildgr = " selected";
            Selectionsio_loadProducts();
        } else {
            var selectfildgr = "";
        }
		$('#sio_api_prgroup').append('<option value="' + data.uuid + '"' + selectfildgr + '>' + depth + data.title + '</option>');
        //
		$.each(data.children, function(index, value) {
			Selectionsio_appendProductGroup(value, depth + '>');
		})
        if(data.uuid == selectedgroup) {
            Selectionsio_loadProducts();
        }
	}
    document.addEventListener('DOMContentLoaded', function() {
document.querySelector("button").onclick = function(){
  document.querySelector("textarea").select();
  document.execCommand('copy');
};
Selectionsio_loadProductGroups();
        if(productUuid.value != "") {
            productUuidField.style.display = 'table-row';
            shortcode.style.display = 'block';
            const shortcodeOptions = {
    calcid: products.value,
    price: apiPriceSelect.value === '1' ? 1 : 0,
    salebtn: apiSaleSelect.value === '1' ? 1 : 0,
    measure: measure.value || ''
};

let shortcodeString = '[Selectionsioproduct_calculator';

for (const key in shortcodeOptions) {
    if (shortcodeOptions[key]) {
        shortcodeString += ` ${key}="${shortcodeOptions[key]}"`;
    }
}
shortcodeString += ']';

productUuidField.style.display = 'table-row';
shortcode.style.display = 'block';
shortcode.innerHTML = `Bitte mit dem Folgenden Shortcode einbinden: ${shortcodeString}`;

document.getElementById('textarea').value = `${shortcodeString}`;
        }
apiPrgroup.addEventListener('change', function() {
Selectionsio_loadProducts();
    });
products.addEventListener('change', function() {
setSelectionsioProductUuid();
    });
thisform.addEventListener('change', function() {
    const shortcodeOptions = {
    calcid: products.value,
    price: apiPriceSelect.value === '1' ? 1 : 0,
    salebtn: apiSaleSelect.value === '1' ? 1 : 0,
    measure: measure.value || ''
};

let shortcodeString = '[Selectionsioproduct_calculator';

for (const key in shortcodeOptions) {
    if (shortcodeOptions[key]) {
        shortcodeString += ` ${key}="${shortcodeOptions[key]}"`;
    }
}
shortcodeString += ']';

productUuidField.style.display = 'table-row';
shortcode.style.display = 'block';
shortcode.innerHTML = `Bitte mit dem Folgenden Shortcode einbinden: ${shortcodeString}`;

document.getElementById('textarea').value = `${shortcodeString}`;
});
measure.addEventListener('change', function() {
if(apiSaleSelect.value == '1' && apiPriceSelect.value == "1" && measure.value != "") {
}
    });
    });
function Selectionsio_copyToClipBoard() {

var content = document.getElementById('textarea');

content.select();
document.execCommand('copy');

msg.innerHTML = "Text in die Zwischenablage kopiert.";
}
</script>
<div id="shortcode" style="display: none;"></div>
<br/>
<div id="msg"></div>
<textarea id="textarea"></textarea><br />
<button onclick="Selectionsio_copyToClipBoard()">Shortcode in Zwischenablage kopieren</button>
<?php
}

// Registriere Einstellungen
function Selectionsioapi_settings_init() {
    register_setting( 'Selectionsioapi-settings-group', 'sio_api_link' );
    register_setting( 'Selectionsioapi-settings-group', 'sio_shop_uuid' );
    register_setting( 'Selectionsioapi-settings-group', 'sio_api_token' );
    register_setting( 'Selectionsioapi-settings-group', 'sio_api_price' );
    register_setting( 'Selectionsioapi-settings-group', 'sio_shop_einheit' );
    register_setting( 'Selectionsioapi-settings-group', 'sio_api_salebutton' );
    register_setting( 'Selectionsioapi-settings-group', 'sio_api_prgroup' );
    register_setting( 'Selectionsioapi-settings-group', 'sio_product_uuid' );
    register_setting( 'Selectionsioapi-settings-group', 'sio_products' );
}
add_action( 'admin_init', 'Selectionsioapi_settings_init' );

function Selectionsioapi_settings_link($links) { 
    $settings_link = '<a href="admin.php?page=Selectionsioapi-settings">Einstellungen</a>'; 
    array_unshift($links, $settings_link); 
    return $links; 
  }
  add_filter("plugin_action_links_" . plugin_basename(__FILE__), 'Selectionsioapi_settings_link');