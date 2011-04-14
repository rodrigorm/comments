<?php
/**
 * CakePHP Comments
 *
 * Copyright 2009 - 2010, Cake Development Corporation
 *                        1785 E. Sahara Avenue, Suite 490-423
 *                        Las Vegas, Nevada 89104
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright 2009 - 2010, Cake Development Corporation
 * @link      http://codaset.com/cakedc/migrations/
 * @package   plugins.comments
 * @license   MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

/**
 * Short description for class.
 *
 * @package		plugins.comments
 * @subpackage	models.behaviors
 */

class BlackHoleException extends Exception {}
class NoActionException extends Exception {}

class CommentableBehavior extends ModelBehavior {
/**
 * Settings array
 *
 * @var array
 * @access public
 */
	public $settings = array();

/**
 * Default settings
 *
 * @var array
 * @access public
 */
	public $defaults = array(
		'commentAlias' => 'Comment',
		'commentModel' => 'Comments.Comment',
		'spamField' => 'is_spam',
		'userModelAlias' => 'UserModel',
		'userModelClass' => 'User',
		'spamValues' => array('spam', 'spammanual'),
		'cleanValues' => array('clean', 'ham'));

/**
 * Setup
 *
 * @param AppModel $model
 * @param array $settings
 * @access public
 */
	public function setup(&$model, $settings = array()) {
		if (!isset($this->settings[$model->alias])) {
			$this->settings[$model->alias] = $this->defaults;
		}
		$this->settings[$model->alias] = am($this->settings[$model->alias], ife(is_array($settings), $settings, array()));

		$cfg = $this->settings[$model->alias];
		$model->bindModel(array('hasMany' => array(
			$cfg['commentAlias'] => array(
				'className' => $cfg['commentModel'],
				'foreignKey' => 'foreign_key',
				'unique' => true,
				'conditions' => '',
				'fields' => '',
				'dependent' => true,
				'order' => '',
				'limit' => '',
				'offset' => '',
				'exclusive' => '',
				'finderQuery' => '',
				'counterQuery' => ''
			),
		)), false);
		$model->{$cfg['commentAlias']}->bindModel(array('belongsTo' => array(
			$model->name => array(
				'className' => $model->name,
				'foreignKey' => 'foreign_key',
				'unique' => true,
				'conditions' => '',
				'fields' => '',
				'counterCache' => true,
				'dependent' => false))), false);
		$model->{$cfg['commentAlias']}->bindModel(array('belongsTo' => array(
			$cfg['userModelAlias'] => array(
				'className' => $cfg['userModelClass'],
				'foreignKey' => 'user_id',
				'conditions' => '',
				'fields' => '',
				'counterCache' => true,
				'order' => ''))), false);
	}

/**
 * Toggle approved field in model record and increment or decrement the associated
 * models comment count appopriately.
 *
 * @param AppModel $model
 * @param mixed commentId
 * @param array $options
 * @return boolean
 * @access public
 */
	public function commentToggleApprove(&$model, $commentId, $options = array()) {
		$commentAlias = $this->settings[$model->alias]['commentAlias'];
		$model->{$commentAlias}->recursive = -1;
		$data = $model->{$commentAlias}->read(null, $commentId);
		if ($data) {
			if ($data[$commentAlias]['approved'] == 0) {
				$data[$commentAlias]['approved'] = 1;
				$direction = 'up';
			} else {
				$data[$commentAlias]['approved'] = 0;
				$direction = 'down';
			}
			if ($model->{$commentAlias}->save($data, false)) {
				$this->changeCommentCount($model, $data[$commentAlias]['foreign_key'], $direction);
				return true;
			}
		}
		return false;
	}

/**
 * Delete comment
 *
 * @param AppModel $model
 * @param mixed commentId
 * @return boolean
 * @access public
 */
	public function commentDelete(&$model, $commentId = null) {
		$commentAlias = $this->settings[$model->alias]['commentAlias'];
		return $model->{$commentAlias}->delete($commentId);
	}

/**
 * Handle adding comments
 *
 * @param AppModel $model Object of the related model class
 * @param mixed $commentId parent comment id, 0 for none
 * @param array $options extra information and comment statistics
 * @return boolean
 * @access public
 */
	public function commentAdd(&$model, $commentId = null, $options = array()) {
		$commentAlias = $this->settings[$model->alias]['commentAlias'];

		$options = array_merge(array('defaultTitle' => '', 'modelId' => null, 'userId' => null, 'data' => array(), 'permalink' => ''), (array)$options);
		extract($options);
		if (isset($options['permalink'])) {
			$model->{$commentAlias}->permalink = $options['permalink'];
		}

		$model->{$commentAlias}->recursive = -1;
		if (!empty($commentId)) {
			$model->{$commentAlias}->id = $commentId;
			if (!$model->{$commentAlias}->find('count', array('conditions' => array(
				$commentAlias . '.id' => $commentId,
				$commentAlias . '.approved' => true,
				$commentAlias . '.foreign_key' => $modelId)))) {
				throw new BlackHoleException(__d('comments', 'Unallowed comment id', true));
			}
		}

		if (!empty($data)) {
			$data[$commentAlias]['user_id'] = $userId;
			$data[$commentAlias]['model'] = $modelName;
			if (!isset($data[$commentAlias]['foreign_key'])) {
				$data[$commentAlias]['foreign_key'] = $modelId;
			}
			if (!isset($data[$commentAlias]['parent_id'])) {
				$data[$commentAlias]['parent_id'] = $commentId;
			}
			if (empty($data[$commentAlias]['title'])) {
				$data[$commentAlias]['title'] = $defaultTitle;
			}

			if (!empty($data['Other'])) {
				foreach($data['Other'] as $spam) {
					if(!empty($spam)) {
						return false;
					}
				}
			}

			$model->{$commentAlias}->create($data);

			if ($model->{$commentAlias}->Behaviors->enabled('Tree')) {
				if (isset($data[$commentAlias]['foreign_key'])) {
					$fk = $data[$commentAlias]['foreign_key'];
				} elseif (isset($data['foreign_key'])) {
					$fk = $data['foreign_key'];
				} else {
					$fk = null;
				}
				$model->{$commentAlias}->Behaviors->attach('Tree', array('scope' => array($commentAlias . '.foreign_key' => $fk)));
			}

			if ($model->{$commentAlias}->save()) {
				$id = $model->{$commentAlias}->id;

				if (!isset($data[$commentAlias]['approved']) || $data[$commentAlias]['approved'] == true) {
					$this->changeCommentCount($model, $modelId);
				}
				return $id;
			} else {
				return false;
			}
		}
		return null;
	}

/**
 * Increment or decrement the comment count cache on the associated model
 *
 * @param Object $model Model to change count of
 * @param mixed $id The id to change count of
 * @param string $direction 'up' or 'down'
 * @access public
 * @return null
 */
	public function changeCommentCount(&$model, $id = null, $direction = 'up') {
		if ($model->hasField('comments')) {
			if ($direction == 'up') {
				$direction = '+ 1';
			} elseif ($direction == 'down') {
				$direction = '- 1';
			} else {
				$direction = null;
			}

			$model->id = $id;
			if (!is_null($direction) && $model->exists(true)) {
				return $model->updateAll(
					array($model->alias . '.comments' => 'comments ' . $direction),
					array($model->alias . '.id' => $id));
			}
		}
		return false;
	}

/**
 * Prepare models association to before fetch data
 *
 * @param array $options
 * @return boolean
 * @access public
 */
	public function commentBeforeFind(&$model, $options) {
		$commentAlias = $this->settings[$model->alias]['commentAlias'];

		$options = array_merge(array('userModel' => $this->settings[$model->alias]['userModelAlias'], 'userData' => null, 'isAdmin' => false), (array)$options);
		extract($options);

		$model->Behaviors->detach('Containable');
		$model->{$commentAlias}->Behaviors->detach('Containable');
		$unbind = array();

		foreach (array('belongsTo', 'hasOne', 'hasMany', 'hasAndBelongsToMany') as $assocType) {
			if (!empty($model->{$commentAlias}->{$assocType})) {
				$unbind[$assocType] = array();
				foreach ($model->{$commentAlias}->{$assocType} as $key => $assocConfig) {
					if (!in_array($key, array($userModel, $model->name))) {
						$unbind[$assocType][] = $key;
					}
				}
			}
		}

		if (!empty($unbind)) {
			$model->{$commentAlias}->unbindModel($unbind, false);
		}

		$model->{$commentAlias}->belongsTo[$model->alias]['fields'] = array('id');
		$model->{$commentAlias}->belongsTo[$userModel]['fields'] = array('id', $model->{$commentAlias}->{$userModel}->displayField, 'slug');
		$conditions = array(
			$commentAlias . '.approved' => 1,
			$commentAlias . '.model' => $model->name
		);
		if (isset($id)) {
			$conditions[$model->alias . '.' . $model->primaryKey] = $id;
		}

		if ($isAdmin) {
			unset($conditions[$commentAlias . '.approved']);
		}

		$model->{$commentAlias}->recursive = 0;
		$spamField = $this->settings[$model->alias]['spamField'];

		if ($model->{$commentAlias}->hasField($spamField)) {
			$conditions[$commentAlias . '.' . $spamField] = $this->settings[$model->alias]['cleanValues'];
		}
		return $conditions;
	}

}
