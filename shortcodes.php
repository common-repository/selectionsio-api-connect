<?php
function Selectionsioproduct_calculator_shortcode($atts) {

    $atts = shortcode_atts( array(
        'price' => '',
        'salebtn' => '',
        'calcid' => '',
        'measure' => '',
    ), $atts );

    $price = $atts['price'];
    $salebtn = $atts['salebtn'];
    $calcid = $atts['calcid'];
    $measure = $atts['measure'];
    $api_link = esc_attr( get_option('sio_api_link') );

    add_action( 'wp_enqueue_scripts', 'Selection_enqueue_calcwp_script' );
    $output = '<script type="text/javascript">';
    $output .= "apiseite = '$api_link';";
    $output .= "productUUId = '$calcid';";
    $output .= 'productLoaded = 0;';
    $output .= 'productValues = {};';
    $output .= "productLanguage = 'EUR';";
    $output .= 'productXml = "";';
    $output .= "productUrl = 'examen-thesis-din-a5';";
    if($salebtn != "") {
        $output .= "salebtn = 1;";
    } else {
        $output .= "salebtn = 0;";
    }
    $output .= '</script>';
    $output .= '<form id="CALCFORM" class=""></form>';
    if($price != "" AND $measure == "") {
    $output .= '<table class="table">';
    $output .= '<tbody>';
    $output .= '<tr>';
    $output .= '<td>Preis (netto):</td>';
    $output .= '<td class="pull-right"><span id="netto"></span></td>';
    $output .= '<tr>
    <td>zzgl.  MwSt.:</td>
    <td class="pull-right"><span id="mwert"></span></td>
</tr>
<tr>
    <td><strong>Preis (brutto):</strong></td>
    <td class="pull-right"><strong><span id="brutto"></span></strong></td>
</tr>
</tbody>
</table>';
    } else if($price != "" AND $measure != "") {
        $output .= '<table class="table">';
    $output .= '<tbody>';
    $output .= '<tr>';
    $output .= '<td>' . $measure . ':</td>';
    $output .= '<td class="pull-right"><span id="netto"></span></td>';
    $output .= '<tr style="display:none">
    <td>zzgl.  MwSt.:</td>
    <td class="pull-right"><span id="mwert"></span></td>
</tr>
<tr style="display:none">
    <td><strong>Preis (brutto):</strong></td>
    <td class="pull-right"><strong><span id="brutto"></span></strong></td>
</tr>
</tbody>
</table>';
    }
    if($salebtn != "") {
    $output .= '<div class="salebtn"></div>';
    }
    return $output;
}

function Selection_enqueue_calcwp_script() {
    wp_enqueue_script( 'calcwp-script', plugins_url( 'js/calcwp.js', __FILE__ ), array(), '1.0.0', true );
}
add_shortcode( 'Selectionsioproduct_calculator', 'Selectionsioproduct_calculator_shortcode' );