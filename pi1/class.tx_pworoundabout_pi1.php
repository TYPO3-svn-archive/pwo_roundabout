<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2012 PWO <info@pwo.ro>
*  All rights reserved
*
*  This script is part of the TYPO3 project. The TYPO3 project is
*  free software; you can redistribute it and/or modify
*  it under the terms of the GNU General Public License as published by
*  the Free Software Foundation; either version 2 of the License, or
*  (at your option) any later version.
*
*  The GNU General Public License can be found at
*  http://www.gnu.org/copyleft/gpl.html.
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/
/**
 * [CLASS/FUNCTION INDEX of SCRIPT]
 *
 * Hint: use extdeveval to insert/update function index above.
 */

require_once(PATH_tslib.'class.tslib_pibase.php');


/**
 * Plugin 'Roundabout' for the 'pwo_roundabout' extension.
 *
 * @author	PWO <info@pwo.ro>
 * @package	TYPO3
 * @subpackage	tx_pworoundabout
 */
class tx_pworoundabout_pi1 extends tslib_pibase {
	var $prefixId      = 'tx_pworoundabout_pi1';		// Same as class name
	var $scriptRelPath = 'pi1/class.tx_pworoundabout_pi1.php';	// Path to this script relative to the extension dir.
	var $extKey        = 'pwo_roundabout';	// The extension key.
	var $pi_checkCHash = true;
	
	/**
	 * The main method of the PlugIn
	 *
	 * @param	string		$content: The PlugIn content
	 * @param	array		$conf: The PlugIn configuration
	 * @return	The content that is displayed on the website
	 */
	function main($content, $conf) {
		$this->conf = $conf;
		$this->pi_setPiVarDefaults();
		$this->pi_loadLL();
		
		$this->pi_initPIflexForm();
		$this->FFConf = array();
		$piFlexForm = $this->cObj->data['pi_flexform'];
		foreach ( $piFlexForm['data'] as $sheet => $data ) {
			foreach ( $data as $lang => $value ) {
				foreach ( $value as $key => $val ) {
					$this->FFConf[$key] = $this->pi_getFFvalue($piFlexForm, $key, $sheet);
					if ($this->FFConf[$key]) {
						$this->conf[$key] = $this->FFConf[$key];
					}
				}
			}
		}
		
		if ( trim($this->conf['templateFile']) ) {
			$templateFile = trim($this->conf['templateFile']);
		} else {
			$templateFile = 'EXT:pwo_roundabout/res/roundabout.html';
		}
		
		$templateCode = $this->cObj->fileResource($templateFile);
		
		if (!$templateCode) {
			return sprintf($this->pi_getLL('template_missing'), $templateFile);
		}
		
		$roundabout_tpl = $this->cObj->getSubpart($templateCode, '###ROUNDABOUT###');	
		$relm_tpl = $this->cObj->getSubpart($roundabout_tpl, '###ROUNDABOUT_ELEMENT###');	
		
		$jq = '';
		// checks if t3jquery is loaded
		if (t3lib_extMgm::isLoaded('t3jquery')) {
			require_once(t3lib_extMgm::extPath('t3jquery').'class.tx_t3jquery.php');
			tx_t3jquery::addJqJS();
		} else {
			if (!$this->conf['noJQfile']) {
				$jq .= '<script src="'.t3lib_extMgm::siteRelPath($this->extKey).'res/jquery-1.7.1.min.js" type="text/javascript"></script>';
			}
		}
		
		if (!$this->conf['noRAfile']) {
			$jq .= '<script src="'.t3lib_extMgm::siteRelPath($this->extKey).'res/jquery.roundabout.min.js" type="text/javascript"></script>';
		}
		
		if ($jq) {
			$GLOBALS['TSFE']->additionalHeaderData['pwo_roundabout_js'] = $jq;
		}
		
		$GLOBALS['TSFE']->tmpl->setup['plugin.'][$this->prefixId.'.']['_CSS_DEFAULT_STYLE'] = 
			TAB.'UL.roundabout {width:'.(intval($this->conf['width'])*2).'px;height:'.(round(intval($this->conf['height'])*1.4)).'px;list-style:none;padding:0;margin:0 auto;}'.LF.
			TAB.'LI.roundabout_element, LI.roundabout_element img {width:'.intval($this->conf['width']).'px;height:'.intval($this->conf['height']).'px;}'.LF.
			TAB.'LI.roundabout_element {background-color:#cccccc;cursor:pointer;}'.LF.
			TAB.'LI.roundabout-in-focus {cursor:default;}';
			
		$elements = array();
		
		switch($this->conf['ratype']) {
			default:
				$dir = $this->conf['path'];
				if ( !trim($dir) ) {
					$elements[] = $this->pi_getLL('error_config');
				} else {
					$elms = t3lib_div::getFilesInDir($dir);
					foreach ( $elms as $elm ) {
						$conf = array(
							'file' => $dir.$elm,
							'file.' => array(
								'width' => $this->conf['width'],
								'height' => $this->conf['height']
							)
						);
						$elements[] = $this->cObj->IMAGE($conf);
					}
				}
			break;
			
			case 'tt_content':
				$records_uid = t3lib_div::intExplode(',', $this->conf['tt_contents']);
				if (count($records_uid)==0) {
					$elements[] = $this->pi_getLL('error_config');
				} else {
					foreach ( $records_uid as $uid ) {
						$conf = array(
							'tables' => 'tt_content',
							'source' => $uid
						);
						$elements[] = $this->cObj->RECORDS($conf);
					}
				}
			break;
		}
		
		$relm_html = '';
		foreach ( $elements as $element ) {
			$markers['###ROUNDABOUT_ELEMENT_CONTENT###'] = $element;
			$relm_html .= $this->cObj->substituteMarkerArray($relm_tpl, $markers);
		}
		$content = $this->cObj->substituteSubpart($roundabout_tpl, '###ROUNDABOUT_ELEMENT###', $relm_html);
		
		$roundabout_settings = array('bearing', 'tilt', 'minZ', 'maxZ', 'minOpacity', 'maxOpacity', 'minScale', 'maxScale', 'duration', 'easing', 'clickToFocus', 'focusBearing', 'debug', 'startingChild', 'reflect', 'floatComparisonThreshold', 'autoplay', 'autoplayDuration', 'autoplayPauseOnHover', 'enableDrag', 'dropDuration', 'dropAnimateTo', 'dragAxis', 'dragFactor', 'triggerFocusEvents', 'triggerBlurEvents', 'responsive');
		foreach ( $roundabout_settings as $sett ) {
			if ( $this->conf[$sett] ) {
				if ( is_numeric($this->conf[$sett]) ) {
					$settings .= $sett.': ' . $this->conf[$sett] . ',';
				} else {
					$settings .= $sett.': "' . $this->conf[$sett] . '",';
				}
			}
		}
		
		$markers = array(
			'###ROUNDABOUT_SETTINGS###' => $settings,
		);
		$content = $this->cObj->substituteMarkerArray($content, $markers);
		
		return $this->pi_wrapInBaseClass($content);
	}
}



if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/pwo_roundabout/pi1/class.tx_pworoundabout_pi1.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/pwo_roundabout/pi1/class.tx_pworoundabout_pi1.php']);
}

?>