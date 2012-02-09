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

App::uses('ClassRegistry', 'Utility');
App::uses('Security', 'Utility');
App::uses('View', 'View');
App::uses('Model', 'Model');
App::uses('CommentWidgetHelper', 'Comments.View/Helper');
App::uses('AppHelper', 'View/Helper');
App::uses('HtmlHelper', 'View/Helper');
App::uses('FormHelper', 'View/Helper');
App::uses('SessionHelper', 'View/Helper');
App::uses('CommentsComponent', 'Comments.Component');

if (!class_exists('Article')) {
	class Article extends CakeTestModel {
	/**
	 * 
	 */
		public $name = 'Article';
	}
}

if (!class_exists('ArticlesTestController')) {
	App::uses('Controller', 'Controller');
	class ArticlesTestController extends Controller {

	/**
	 * @var string
	 */
		public $name = 'ArticlesTest';

	/**
	 * @var array
	 */
		public $uses = array('Article');

	/**
	 * @var array
	 */
		public $components = array('Comments.Comments');

	/**
	 * Overrides Controller::redirect() to log the redirected url
	 * (non-PHPdoc)
	 * @see cake/libs/controller/Controller#redirect($url, $status, $exit)
	 */
		public function redirect($url, $status = NULL, $exit = true) {
			$this->redirectUrl = $url;
		}

	}
}

/**
 * Comment Widget Helper Test
 *
 * @package comments
 * @subpackage comment.tests.cases.helpers
 */
class CommentWidgetHelperTest extends CakeTestCase {

/**
 * fixtures property
 *
 * @var array
 */
	public $fixtures = array(
		'plugin.comments.comment',
		'plugin.comments.user',
		'plugin.comments.article');

/**
 * Helper being tested
 * @var CommentWidgetHelper
 */
	public $CommentWidget = null;
	
/**
 * Controller with commentable related actions for testing purpose
 * @var ArticlesTestController
 */
	public $Controller = null;
	
/**
 * Current view object
 * @var View
 */
	public $View = null;
	
/**
 * Mock object for Js helper
 * @var JsHelper
 */
	public $Js = null;
	
/**
 * Start test method
 *
 * @return void
 */
	public function startTest($method) {
		parent::startTest($method);

		$this->Controller = ClassRegistry::init('ArticlesTestController');
		$this->View = $this->getMock('View', array('element'), array(new Controller()));
		$this->CommentWidget = new CommentWidgetHelper($this->View);
		$this->CommentWidget->Form = new FormHelper($this->View);
		$this->CommentWidget->Html = new HtmlHelper($this->View);
		$this->Js = $this->getMock('AppHelper', array('link', 'get', 'effect'), array($this->View));
		$this->CommentWidget->Js = $this->Js;
		$this->CommentWidget->request->params['action'] = 'view';
		
		if (!in_array($method, array('testInitialize', 'testOptions'))) {
			$this->CommentWidget->initialize();
		}
	}
	
/**
 * Test helper instance
 * 
 * @return void
 */
	public function testInstance() {
		$this->assertTrue(is_a($this->CommentWidget, 'CommentWidgetHelper'));
	}
	
/**
 * Test initialize
 * 
 * @return void
 */
	public function testInitialize() {
		$this->assertTrue(empty($this->CommentWidget->globalParams));
		$this->CommentWidget->initialize();
		$this->assertFalse(empty($this->CommentWidget->globalParams));
	}

/**
 * Test beforeRender callback
 * 
 * @return void
 */
	public function testBeforeRender() {
		$this->assertTrue(empty($this->View->viewVars));
		$this->CommentWidget->beforeRender(null);
		$this->assertFalse($this->CommentWidget->enabled);
		
		$this->View->viewVars['commentParams'] = array(
			'displayType' => 'flat');
		$this->CommentWidget->beforeRender(null);
		$this->assertFalse($this->CommentWidget->enabled);
		
		$this->View->viewVars['commentParams'] = array(
			'displayType' => 'flat',
			'viewComments' => 'commentsData');
		$this->CommentWidget->beforeRender(null);
		$this->assertTrue($this->CommentWidget->enabled);
	}
	
/**
 * Test options method
 * 
 * @return void
 */
	public function testOptions() {
		$this->assertTrue(empty($this->CommentWidget->globalParams));
		$this->Js->expects($this->any())->method('get')->will($this->returnValue($this->Js));
		$this->Js->expects($this->any())->method('effect')->will($this->returnValue(''));
		$options = array(
			'target' => 'test',
			'foo' => 'bar');

		$this->CommentWidget->options($options);
		$this->assertEqual(count($this->CommentWidget->globalParams), 9);
		$this->assertEqual($this->CommentWidget->globalParams['target'], 'test');
		$this->assertEqual($this->CommentWidget->globalParams['foo'], 'bar');
		
		$this->CommentWidget->options(array());
		$this->assertEqual(count($this->CommentWidget->globalParams), 9);
		$this->assertFalse($this->CommentWidget->globalParams['target']);
		$this->assertEqual($this->CommentWidget->globalParams['foo'], 'bar');
	}
	
/**
 * Test display method
 * 
 * @return void
 */
	public function testDisplay() {
		$this->CommentWidget->enabled = false;
		$this->assertEqual($this->CommentWidget->display(), '');
	}

	public function testBasicDisplay() {
		$this->CommentWidget->enabled = true;
		$initialParams = $this->CommentWidget->globalParams; 
		$Article = ClassRegistry::init('Article');
		Configure::write('Routing.admin', 'admin');

		// Test a basic display call
		$currArticle = $Article->findById(1);
		$this->View->passedArgs = array(
			'foo' => 'bar',
			'article-slug');
		$this->View->viewVars = array(
			'article' => $currArticle,
			'commentParams' => array(
				'viewComments' => 'commentsData',
				'modelName' => 'Article',
				'userModel' => 'User'),
		);
		$expectedParams = array(
			'comments/flat/main', 
			array_merge(
				$initialParams,
				array(
					'viewRecord' => $currArticle['Article'],
					'viewRecordFull' => $currArticle),
				$this->View->viewVars['commentParams'],
				array(
					'url' => array('article-slug'),
					'allowAddByAuth' => false,
					'allowAddByModel' => 1,
					'adminRoute' => 'admin',
					'isAddMode' => false,
					'theme' => 'flat')
				)
		);

		$this->View->expects($this->at(0))->method('element')->with($this->equalTo($expectedParams[0]), $this->equalTo($expectedParams[1]));
		$expected = 'Here are your comments!';
		$this->View->expects($this->at(1))->method('element')->will($this->returnValue($expected));
		$result = $this->CommentWidget->display();
		$this->assertEqual($result, $expected);
	}

	public function testDisplayWithOptions() {
		$this->CommentWidget->enabled = true;
		$initialParams = $this->CommentWidget->globalParams; 
		$Article = ClassRegistry::init('Article');
		Configure::write('Routing.admin', 'admin');

		// Test a basic display call
		$currArticle = $Article->findById(1);
		$this->View->passedArgs = array(
			'foo' => 'bar',
			'article-slug');
		$this->View->viewVars = array(
			'article' => $currArticle,
			'commentParams' => array(
				'viewComments' => 'commentsData',
				'modelName' => 'Article',
				'userModel' => 'User'),
		);
		$expectedParams = array(
			'comments/threaded_custom/main', 
			array_merge(
				$initialParams,
				array(
					'viewRecord' => $currArticle['Article'],
					'viewRecordFull' => $currArticle
				),
				$this->View->viewVars['commentParams'],
				array(
					'url' => array('article-slug'),
					'allowAddByAuth' => false,
					'allowAddByModel' => 1,
					'adminRoute' => 'admin',
					'isAddMode' => false,
					'theme' => 'threaded_custom',
					'displayType' => 'threaded',
					'subtheme' => 'custom'
				)
			)
		);
		$this->View->expects($this->at(0))->method('element')->with($this->equalTo($expectedParams[0]), $this->equalTo($expectedParams[1]));
		$expected = 'Here are your comments!';
		$this->View->expects($this->at(1))->method('element')->will($this->returnValue($expected));
		$options = array(
			'displayType' => 'threaded',
			'subtheme' => 'custom');
		$result = $this->CommentWidget->display($options);
		$this->assertEqual($result, $expected);


	}

	public function testDisplayOtherCases() {
		$this->CommentWidget->enabled = true;
		$initialParams = $this->CommentWidget->globalParams; 
		$Article = ClassRegistry::init('Article');
		Configure::write('Routing.admin', 'admin');

		// Test a basic display call
		$currArticle = $Article->findById(1);
		$this->View->passedArgs = array(
			'foo' => 'bar',
			'article-slug');
		$this->View->viewVars = array(
			'article' => $currArticle,
			'commentParams' => array(
				'viewComments' => 'commentsData',
				'modelName' => 'Article',
				'userModel' => 'User'),
		);
		$this->CommentWidget->initialize();
		$this->View->request->params['userslug'] = 'example-user';
		unset($this->View->viewVars['article']);
		$expectedParams = array(
			'comments/threaded_custom/main', 
			array_merge(
				$initialParams,
				array(
					'viewRecord' => $currArticle['Article'],
					'viewRecordFull' => $currArticle
				),
				$this->View->viewVars['commentParams'],
				array(
					'url' => array('example-user', 'article-slug'),
					'allowAddByAuth' => false,
					'allowAddByModel' => 1,
					'adminRoute' => 'admin',
					'isAddMode' => false,
					'theme' => 'threaded_custom',
					'displayType' => 'threaded',
					'subtheme' => 'custom',
					'viewRecord' => array(),
					'viewRecordFull' => array()
				)
			)
		);
		$this->View->expects($this->at(0))->method('element')->with($this->equalTo($expectedParams[0]), $this->equalTo($expectedParams[1]));
		$expected = 'Here are your comments!';
		$this->View->expects($this->at(1))->method('element')->will($this->returnValue($expected));
		$options = array(
			'displayType' => 'threaded',
			'subtheme' => 'custom');
		$result = $this->CommentWidget->display($options);
		$this->assertEqual($result, $expected);
	}

/**
 * Test display method with a custom url
 * 
 * @return void
 */
	public function testDisplayCustomUrl() {
		$initialParams = $this->CommentWidget->globalParams; 
		$Article = ClassRegistry::init('Article');
		Configure::write('Routing.admin', 'admin');

		// Test a basic display call
		$currArticle = $Article->findById(1);
		$this->View->passedArgs = array(
			'foo' => 'bar',
			'article-slug');
		$this->View->viewVars = array(
			'article' => $currArticle,
			'commentParams' => array(
				'viewComments' => 'commentsData',
				'modelName' => 'Article',
				'userModel' => 'User'),
		);
		$expectedParams = array(
			'comments/flat/main', 
			array_merge(
				$initialParams,
				array(
					'viewRecord' => $currArticle['Article'],
					'viewRecordFull' => $currArticle),
				$this->View->viewVars['commentParams'],
				array(
					'url' => array('action' => 'other', 'param1'),
					'allowAddByAuth' => false,
					'allowAddByModel' => 1,
					'adminRoute' => 'admin',
					'isAddMode' => false,
					'theme' => 'flat')
				)
		);
		$this->CommentWidget->options(array('url' => array('action' => 'other', 'param1')));
		$this->View->expects($this->at(0))->method('element')->with($this->equalTo($expectedParams[0]), $this->equalTo($expectedParams[1]));
		$expected = 'Here are your comments!';
		$this->View->expects($this->at(1))->method('element')->will($this->returnValue($expected));
		$result = $this->CommentWidget->display();
		$this->assertEqual($result, $expected);
	}

/**
 * Test link method
 * 
 * @return void
 */
	public function testLink() {
		$result = $this->CommentWidget->link('Foobar', '/foo', array('class' => 'bar'));
		$expected = array(
			'a' => array('href' => '/foo', 'class' => 'bar'), 
			'Foobar', 
			'/a');
		$this->assertTags($result, $expected);

		$this->Js->expects($this->any())->method('get')->will($this->returnValue($this->Js));
		$this->Js->expects($this->any())->method('effect')->will($this->returnValue(''));
		
		$this->CommentWidget->options(array('target' => 'wrapper', 'ajaxOptions' => array('update' => 'wrapper'))); 
		$this->Js->expects($this->once())->method('link')->with(
			$this->equalTo('Foobar'),
			$this->equalTo('/foo'),
			$this->equalTo(array(
				'update' => 'wrapper',
				'class' => 'bar'
			))
		)->will($this->returnValue('/ajaxFoo'));
		$result = $this->CommentWidget->link('Foobar', '/foo', array('class' => 'bar'));
		$this->assertEqual($result, '/ajaxFoo');
	}
	
/**
 * Test prepareUrl method
 * 
 * @return void
 */
	public function testPrepareUrl() {
		$expected = $url = array(
			'controller' => 'articles',
			'action' => 'view',
			'my-first-article');
		$this->assertEqual($this->CommentWidget->prepareUrl($url), $expected);

		$this->Js->expects($this->any())->method('get')->will($this->returnValue($this->Js));
		$this->Js->expects($this->any())->method('effect')->will($this->returnValue(''));
		
		$this->CommentWidget->options(array(
			'target' => 'placeholder',
			'ajaxAction' => 'add'));
		$expected['action'] = 'add';
		$this->assertEqual($this->CommentWidget->prepareUrl($url), $expected);
		
		$this->CommentWidget->options(array(
			'target' => 'placeholder',
			'ajaxAction' => array(
				'controller' => 'comments',
				'action' => 'add')));
		$expected = array(
			'controller' => 'comments',
			'action' => 'add',
			'my-first-article');
		$this->assertEqual($this->CommentWidget->prepareUrl($url), $expected);
	}
	
/**
 * Test allowAnonymousComment method
 * 
 * @return void
 */
	public function testAllowAnonymousComment() {
		$this->assertFalse($this->CommentWidget->globalParams['allowAnonymousComment']);
		$this->CommentWidget->options(array('allowAnonymousComment' => true));
		$this->assertTrue($this->CommentWidget->globalParams['allowAnonymousComment']);
	}
	
/**
 * Test element method
 * 
 * @return void
 */
	public function testElement() {
		$this->CommentWidget->options(array('theme' => 'flat'));
		
		$expectedParams = array(
			'comments/flat/view',
			array(
				'target' => false,
				'ajaxAction' => false,
				'displayUrlToComment' => false,
				'urlToComment' => '',
				'allowAnonymousComment'  => false,
				'url' => null,
				'ajaxOptions' => array(),
				'viewInstance' => null,
				'theme' => 'flat')
		);
		$this->View->expects($this->at(0))->method('element')->with($this->equalTo($expectedParams[0]), $this->equalTo($expectedParams[1]));
		$expected = 'Comment element content';
		$this->View->expects($this->at(1))->method('element')->will($this->returnValue($expected));
		$this->assertEqual($this->CommentWidget->element('view'), $expected);
	}
		
	public function testMissingElementInPorjectElementsPath() {
		$this->CommentWidget->options(array('theme' => 'flat'));
		
		$expectedParams = array(
			'comments/flat/view',
			array(
				'target' => false,
				'ajaxAction' => false,
				'displayUrlToComment' => false,
				'urlToComment' => '',
				'allowAnonymousComment'  => false,
				'url' => null,
				'ajaxOptions' => array(),
				'viewInstance' => null,
				'theme' => 'flat'
			)
		);
		// Test missing element in project elements path. The helper must try to search the element from the comments plugin
		$this->View->expects($this->at(0))->method('element')->with($this->equalTo($expectedParams[0]), $this->equalTo($expectedParams[1]))->will($this->returnValue('Not Found: /path/to/project/views/elements/comments/flat/view.ctp'));
		$expectedParams[1]['plugin'] = 'comments';
		$expected = 'Comment element content';
		$this->View->expects($this->at(1))->method('element')->with($this->equalTo($expectedParams[0]), $this->equalTo($expectedParams[1]))->will($this->returnValue($expected));
		$this->assertEqual($this->CommentWidget->element('view'), $expected);
		unset($expectedParams[1]['plugin']);
		
	}
		
	public function testElementParams() {
		$this->CommentWidget->options(array('theme' => 'flat'));
		
		$expectedParams = array(
			'comments/flat/view',
			array(
				'target' => false,
				'ajaxAction' => false,
				'displayUrlToComment' => false,
				'urlToComment' => '',
				'allowAnonymousComment'  => false,
				'url' => null,
				'ajaxOptions' => array(),
				'viewInstance' => null,
				'theme' => 'flat'
			)
		);
		// Test params: they must be passed to the element "as is". Note that the theme has not effect on the element being fetched
		$expectedParams[1]['target'] = 'wrapper'; 
		$expectedParams[1]['theme'] = 'threaded';
		$this->View->expects($this->at(0))->method('element')->with($this->equalTo($expectedParams[0]), $this->equalTo($expectedParams[1]));
		$expected = 'Comment element content';
		$this->View->expects($this->at(1))->method('element')->will($this->returnValue($expected));
		$this->assertEqual($this->CommentWidget->element('view', array('target' => 'wrapper', 'theme' => 'threaded')), $expected);
	}

/**
 * End test method
 *
 * @return void
 */
	public function endTest($method) {
		unset($this->CommentWidget, $this->Controller, $this->View);
		ClassRegistry::flush();
	}
}