<?php
/**
 * Copyright 2009-2010, Cake Development Corporation (http://cakedc.com)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright Copyright 2009-2010, Cake Development Corporation (http://cakedc.com)
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

/**
 * Comment Widget Helper
 *
 * @package comments
 * @subpackage comments.views.helpers
 */
class CommentWidgetHelper extends AppHelper {

/**
 * Helpers
 *
 * @var array
 */
	public $helpers = array('Html', 'Js' => array('Jquery'));

/**
 * Flag if this widget is properly configured
 *
 * @var boolean
 */
	public $enabled = true;

/**
 * Helper options
 *
 * @var array
 */
	public $options = array(
		'target' => false,
		'ajaxAction' => false,
		'displayUrlToComment' => false,
		'urlToComment' => '',
		'allowAnonymousComment'  => false,
		'url' => null,
		'ajaxOptions' => array(),
		'viewInstance' => null
	);

/**
 * List of settings needed to be not empty in $this->params['Comments']
 *
 * @var array
 */
	protected $__passedParams = array('displayType', 'viewComments');

/**
 * Global widget parameters
 *
 * @var string
 */
	public $globalParams = array();

/**
 * Initialize callback
 *
 * @return void
 */
	public function initialize() {
		$this->options(array());
	}

/**
 * Before render Callback
 *
 * @return void
 */
	public function beforeRender($viewFile) {
		parent::beforeRender($viewFile);

		$this->enabled = !empty($this->_View->viewVars['commentParams']);
		if ($this->enabled) {
			foreach ($this->__passedParams as $param) {
				if (empty($this->_View->viewVars['commentParams'][$param])) {
					$this->enabled = false;
					break;
				}
			}
		}
	}

/**
 * Setup options
 *
 * @param array $data
 * @return void
 */
	public function options($data) {
		$this->globalParams = array_merge(array_merge($this->globalParams, $this->options), (array)$data);
		if (!empty($this->globalParams['target']) && empty($this->globalParams['ajaxOptions'])) {
			$this->globalParams['ajaxOptions'] = array(
				'rel' => 'nofollow',
				'update' => $this->globalParams['target'],
				'evalScripts' => true,
				'before' =>
					$this->Js->get($this->globalParams['target'] . ' .comments')->effect('fadeOut', array('buffer' => false)) .
					$this->Js->get('#busy-indicator')->effect('show', array('buffer' => false)),
				'complete' =>
					$this->Js->get($this->globalParams['target'] . ' .comments')->effect('fadeIn', array('buffer' => false)) .
					$this->Js->get('#busy-indicator')->effect('hide', array('buffer' => false)),
			);
		}
	}

/**
 * Display comments
 *
 * ### Params
 *
 * - `displayType` The primary type of comments you want to display.  Default is flat, and built in types are
 *    flat, threaded and tree.
 * - `subtheme` an optional subtheme to use for rendering the comments, used with `displayType`.
 *    If your comments type is 'flat' and you use `'theme' => 'mytheme'` in your params.
 *   `elements/comments/flat_mytheme` is the directory the helper will look for your elements in.
 *
 * @param array $params Parameters for the comment rendering
 * @return string Rendered elements.
 */
	public function display($params = array()) {
		$result = '';
		if ($this->enabled) {
			$params = Set::merge($this->_View->viewVars['commentParams'], $params);
			if (isset($params['displayType'])) {
				$theme = $params['displayType'];
				if (isset($params['subtheme'])) {
					$theme .= '_' . $params['subtheme'];
				}
			} else {
				$theme = 'flat';
			}

			if (!is_null($this->globalParams['url'])){
				$url = array();
			} else {
				$url = array();
				if (isset($this->_View->request->params['userslug'])) {
					$url[] = $this->_View->request->params['userslug'];
				}
				if (!empty($this->_View->passedArgs)) {
					foreach ($this->_View->passedArgs as $key => $value) {
						if (is_numeric($key)) {
							$url[] = $value;
						}
					}
				}
			}

			$model = $params['modelName'];
			$viewRecord = $this->globalParams['viewRecord'] = array();
			$viewRecordFull = $this->globalParams['viewRecordFull'] = array();
			if (isset($this->_View->viewVars[Inflector::variable($model)][$model])) {
				$viewRecord = $this->_View->viewVars[Inflector::variable($model)][$model];
				$viewRecordFull = $this->_View->viewVars[Inflector::variable($model)];
			}

			if (isset($viewRecord['allow_comments'])) {
				$allowAddByModel = ($viewRecord['allow_comments'] == 1);
			} else {
				$allowAddByModel = 1;
			}
			$isAddMode = (isset($params['comment']) && !isset($params['comment_action']));
			$adminRoute = Configure::read('Routing.admin');

			$allowAddByAuth = ($this->globalParams['allowAnonymousComment'] || !empty($this->_View->viewVars['isAuthorized']));

			$params = array_merge($params, compact('url', 'allowAddByAuth', 'allowAddByModel', 'adminRoute', 'isAddMode', 'viewRecord', 'viewRecordFull', 'theme'));
			$this->globalParams = Set::merge($this->globalParams, $params);
			$result = $this->element('main');
		}
		return $result;
	}

/**
 * Link method used to add additional options in ajax mode
 *
 * @param string $title
 * @param mixed $url
 * @param array $options
 * @return string, url
 */
	public function link($title, $url='', $options = array()) {
		if ($this->globalParams['target']) {
			return $this->Js->link($title, $this->prepareUrl($url), array_merge($this->globalParams['ajaxOptions'], $options));
		} else {
			return $this->Html->link($title, $url, $options);
		}
	}

/**
 * Modify url in case of ajax request. Set ajaxAction that supposed to be stored in same controller.
 *
 * @param array $url
 * @return array, generated url
 */
	public function prepareUrl(&$url) {
		if ($this->globalParams['target']) {
			if (is_string($this->globalParams['ajaxAction'])) {
				$url['action'] = $this->globalParams['ajaxAction'];
			} elseif(is_array($this->globalParams['ajaxAction'])) {
				$url = array_merge($url, $this->globalParams['ajaxAction']);
			}
		}
		return $url;
	}

/**
 * Render element from global theme
 *
 * @param string $name
 * @param array $params
 * @return string, rendered element
 */
	public function element($name, $params = array()) {
		if (strpos($name, '/') === false) {
			$name = 'comments/' . $this->globalParams['theme'] . '/' . $name;
		}
		$params = Set::merge($this->globalParams, $params);
		$response = $this->_View->element($name, $params);
		if (is_null($response) || strpos($response, 'Not Found:') !== false) {
			$response = $this->_View->element($name, array_merge($params, array('plugin' => 'comments')));
		}
		return $response;
	}

/**
 * Basic tree callback, used to generate tree of items element, rendered based on actual theme
 *
 * @param array $data
 * @return string
 */
	public function treeCallback($data) {
		return $this->element('item', array('comment' => $data['data'], 'data' => $data));
	}
}