<?php
/**
 * EmailNotification Component
 * This class offers tools to send notification emails.
 *
 * @copyright (c) 2015-present Bolt Softwares Pvt Ltd
 * @licence GNU Affero General Public License http://www.gnu.org/licenses/agpl-3.0.en.html
 */
class EmailNotificatorComponent extends Component {

/**
 * @var Permission $Permission model instance
 */
	public $Permission;

/**
 * @var User $User model instance
 */
	public $User;

/**
 * @var Resource $v model instance
 */
	public $Resource;

/**
 * @var Secret $Secret model instance
 */
	public $Secret;

/**
 * @var EmailNotification $EmailNotification model instance
 */
	public $EmailNotification;

/**
 * Called before the Controller::beforeFilter().
 *
 * @param Controller $controller Controller with components to initialize
 * @return void
 * @link http://book.cakephp.org/2.0/en/controllers/components.html#Component::initialize
 */
	public function initialize(Controller $controller) {
		$this->Permission = Common::getModel('Permission');
		$this->User = Common::getModel('User');
		$this->Resource = Common::getModel('Resource');
		$this->Secret = Common::getModel('Secret');
		$this->EmailNotification = Common::getModel('EmailNotification');
		parent::initialize($controller);
	}

/**
 * Return the email author information like name and avatar
 * This data is required in the email template
 *
 * @param string $userId UUID
 * @return array $author, empty if not found or null on error
 */
	protected function _getAuthorInfo($userId) {
		$author = $this->User->find(
			'first',
			[
				'conditions' => [
					'User.id' => $userId,
				],
				'fields' => [
					'username'
				],
				'contain' => [
					'Profile' => [
						'fields' => [
							'Profile.first_name',
							'Profile.last_name',
						],
						'Avatar' => [
							'fields' => [
								'Avatar.*'
							]
						]
					],
				]
			]
		);

		return $author;
	}

/**
 * Send a notification email regarding a new password that has been shared with the user.
 *
 * @param string $toUserId uuid of the recipient
 * @param array $data
 *   variables to pass to the template which should contain
 *     resource_id the resource id
 *     sharer_id the user who is sharing the resource
 * @return void
 */
	public function passwordSharedNotification($toUserId, $data) {
		// get resource.
		$resource = $this->Resource->find(
			'first',
			[
				'conditions' => [
					'Resource.id' => $data['resource_id']
				],
				'fields' => [
					'Resource.name',
					'Resource.username',
					'Resource.uri',
					'Resource.description'
				],
				'contain' => [
					'Secret' => [
						'fields' => [
							'Secret.data',
							'Secret.modified',
						],
						'conditions' => [
							'Secret.user_id' => $toUserId
						],
					]
				]
			]
		);

		$recipient = $this->User->find(
			'first', [
				'conditions' => [
					'User.id' => $toUserId
				],
			]
		);

		// Get sharer info.
		$sharer = $this->_getAuthorInfo($data['sharer_id']);

		// Send notification.
		$this->EmailNotification->send(
			$recipient['User']['username'],
			__("%s shared %s with you", $sharer['Profile']['first_name'], $resource['Resource']['name']),
			[
				'sender' => $sharer,
				'resource' => $resource,
			],
			'new_password_share'
		);
	}

/**
 * Send a notification email regarding a new account created for a user.
 *
 * @param uuid $toUserId user id of the recipient
 * @param array $data
 *   variables to pass to the template which should contain
 *     - creator_id the user who has created the account
 *     - token the token
 * @return void
 */
	public function accountCreationNotification($toUserId, $data) {
		// Get recipient info.
		$recipient = $this->User->find(
			'first',
			[
				'conditions' => [
					'User.id' => $toUserId,
				],
				'contain' => [
					'Profile'
				],
			]
		);

		// Get invite sender.
		$sender = $this->_getAuthorInfo($data['creator_id']);

		$self = isset($data['self']) && $data['self'] == true;

		$subject = $self ?
			__("Welcome to passbolt, %s!", $recipient['Profile']['first_name']) :
			__("%s created an account for you!", $sender['Profile']['first_name']);

		$template = $self ? 'account_creation_self' : 'account_creation';

		// Send notification.
		$this->EmailNotification->send(
			$recipient['User']['username'],
			$subject,
			[
				'sender' => $sender,
				'account' => $recipient,
				'token' => $data['token'],
			],
			$template
		);
	}
}