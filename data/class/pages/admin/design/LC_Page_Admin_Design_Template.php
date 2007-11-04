<?php
/*
 * This file is part of EC-CUBE
 *
 * Copyright(c) 2000-2007 LOCKON CO.,LTD. All Rights Reserved.
 *
 * http://www.lockon.co.jp/
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
 */

  // {{{ requires
require_once(CLASS_PATH . "pages/LC_Page.php");
require_once(DATA_PATH . "module/Tar.php");

/**
 * テンプレート設定 のページクラス.
 *
 * @package Page
 * @author LOCKON CO.,LTD.
 * @version $Id$
 */
class LC_Page_Admin_Design_Template extends LC_Page {

    // }}}
    // {{{ functions

    /** テンプレートデータ種別 */
    var $arrSubnavi = array(
                     'title' => array(
                                1 => 'top',
                                2 => 'product',
                                3 => 'detail',
                                4 => 'mypage'
                                             ),
                     'name' =>array(
                                1 => 'TOPページ',
                                2 => '商品一覧ページ',
                                3 => '商品詳細ページ',
                                4 => 'MYページ'
                              )
                     );

    /**
     * Page を初期化する.
     *
     * @return void
     */
    function init() {
        parent::init();
	    $this->tpl_mainpage = 'design/template.tpl';
	    $this->tpl_subnavi  = 'design/subnavi.tpl';
	    $this->tpl_subno    = 'template';
	    $this->tpl_mainno   = "design";
	    $this->tpl_subtitle = 'テンプレート設定';
	    $this->arrErr  = array();
	    $this->arrForm = array();
    }

    /**
     * Page のプロセス.
     *
     * @return void
     */
    function process() {
    	// 認証可否の判定
		$objSession = new SC_Session();
		SC_Utils::sfIsSuccess($objSession);
    	
		// uniqidをテンプレートへ埋め込み
		$this->uniqid = $objSession->getUniqId();
		
		$objView = new SC_AdminView();
		
		switch($this->lfGetMode()) {
		
		// 登録ボタン押下時
		case 'register':
		    // 画面遷移の正当性チェック
		    if (!SC_Utils::sfIsValidTransition($objSession)) {
		        sfDispError('');
		    }
		    // パラメータ検証
		    $objForm = $this->lfInitRegister();
		    if ($objForm->checkError()) {
		        sfDispError('');
		    }
		
		    $template_code = $objForm->getValue('template_code');
		
		    if ($template_code == 'default') {
		        $this->lfRegisterTemplate('');
		        $this->tpl_onload="alert('登録が完了しました。');";
		        break;
		    }
		
		    // DBへ使用するテンプレートを登録
		    $this->lfRegisterTemplate($template_code);
		
		    // テンプレートの上書き
		    $this->lfChangeTemplate($template_code);
		
		    // XXX コンパイルファイルのクリア処理を行う
		    $objView->_smarty->clear_compiled_tpl();
		
		    // 完了メッセージ
		    $this->tpl_onload="alert('登録が完了しました。');";
		    break;
		
		// 削除ボタン押下時
		case 'delete':
		    // 画面遷移の正当性チェック
		    if (!sfIsValidTransition($objSession)) {
		        sfDispError('');
		    }
		    // パラメータ検証
		    $objForm = $this->lfInitDelete();
		    if ($objForm->checkError()) {
		        sfDispError('');
		    }
		
		    $template_code = $objForm->getValue('template_code_delete');
		    if ($template_code == $this->lfGetNowTemplate()) {
		        $this->tpl_onload = "alert('選択中のテンプレートは削除出来ません');";
		        break;
		    }
		
		    $this->lfDeleteTemplate($template_code);
		    break;
		
		// プレビューボタン押下時
		case 'preview':
		    break;
		
		default:
		    break;
		}
		
		// defaultパラメータのセット
		$this->templates = $this->lfGetAllTemplates();
		$this->now_template = $this->lfGetNowtemplate();
		
		// 画面の表示
		$objView->assignobj($this);
		$objView->display(MAIN_FRAME);
    }

    /**
     * デストラクタ.
     *
     * @return void
     */
    function destroy() {
        parent::destroy();
    }

	function lfGetMode(){
	    if (isset($_POST['mode'])) return $_POST['mode'];
	}
	
	function lfInitRegister() {
	    $objForm = new SC_FormParam();
	    $objForm->addParam(
	        'template_code', 'template_code', STEXT_LEN, '',
	        array("EXIST_CHECK","SPTAB_CHECK","MAX_LENGTH_CHECK", "ALNUM_CHECK")
	    );
	    $objForm->setParam($_POST);
	
	    return $objForm;
	}
	
	function lfInitDelete() {
	    $objForm = new SC_FormParam();
	    $objForm->addParam(
	        'template_code_delete', 'template_code_delete', STEXT_LEN, '',
	        array("EXIST_CHECK","SPTAB_CHECK","MAX_LENGTH_CHECK", "ALNUM_CHECK")
	    );
	    $objForm->setParam($_POST);
	
	    return $objForm;
	}
	
	/**
	 * 現在適用しているテンプレートパッケージ名を取得する.
	 *
	 * @param void
	 * @return string テンプレートパッケージ名
	 */
	function lfGetNowTemplate() {
	    $objQuery = new SC_Query();
	    $arrRet = $objQuery->select('top_tpl', 'dtb_baseinfo');
	    if (isset($arrRet[0]['top_tpl'])) {
	        return $arrRet[0]['top_tpl'];
	    }
	    return null;
	}
	
	/**
	 * 使用するテンプレートをDBへ登録する
	 */
	function lfRegisterTemplate($template_code) {
	    $objQuery = new SC_Query();
	    $objQuery->update(
	        'dtb_baseinfo',
	        array('top_tpl'=> $template_code)
	    );
	}
	/**
	 * テンプレートを上書きコピーする.
	 */
	function lfChangeTemplate($template_code){
	    $from = TPL_PKG_PATH . $template_code . '/user_edit/';
	
	    if (!file_exists($from)) {
	        $mess = $from . 'は存在しません';
	    } else {
	        $to = USER_PATH;
	        $mess = sfCopyDir($from, $to, '', true);
	    }
	    return $mess;
	}
	
	function lfGetAllTemplates() {
	    $objQuery = new SC_Query();
	    $arrRet = $objQuery->select('*', 'dtb_templates');
	    if (empty($arrRet)) return array();
	
	    return $arrRet;
	}
	
	function lfDeleteTemplate($template_code) {
	    $objQuery = new SC_Query();
	    $objQuery->delete('dtb_templates', 'template_code = ?', array($template_code));
	
	    sfDelFile(TPL_PKG_PATH . $template_code);
	}
}
?>
