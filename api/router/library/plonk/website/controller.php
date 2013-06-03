<?php

/**
 * Plonk - Plonk PHP Library
 * Controller Class - Module Controller
 *  
 * @package		Plonk
 * @subpackage	website
 * @author		Bramus Van Damme <bramus.vandamme@kahosl.be>
 * @version		1.2 - Now plays nice with mod_rewrite
 * 				1.1 - Now includes the version number (was missing from 1.0)
 * 				1.0 - First release
 */

// load dependencies
require_once 'plonk/filter/filter.php';

class PlonkController
{
	
	
	/**
	 * The version of this class
	 */
	const version = 1.2;
	
	
	/**
	 * The current View
	 * @var string
	 */
	protected $view;
	
	
	/**
	 * The current action
	 * @var string
	 */
	protected $action;


	/**
	 * The allowed views
	 * @var array
	 */
	protected $views;
	
	
	/**
	 * The allowed actions
	 * @var array
	 */
	protected $actions;
	
	
	/**
	 * The Main Layout
	 * @var PlonkTemplate
	 */
	protected $mainTpl;
	
	
	/**
	 * The Page Specific Layout
	 * @var PlonkTemplate
	 */
	protected $pageTpl;

	/**
	 * The url parts
	 * @var Array
	 */
	protected $urlParts;
	
	
	/**
	 * Constructor
	 * @param array $urlParts
	 */
	public function __construct(array $urlParts)
	{
		$this->urlParts = $urlParts;
	}
	
	
	/**
	 * Define the action to perform
	 * @return void
	 */
	final private function defineAction()
	{

		// formAction set, get it.
		if (PlonkFilter::getPostValue('formAction') !== null) {

			// make it ourselves a bit easier to work
			$formAction = PlonkFilter::getPostValue('formAction');
			
			// chop off 'do' from the action
			if (PlonkFilter::startsWith($formAction, 'do')) {
				$this->action = strtolower(substr($formAction,2));
			} else {
				$this->action = strtolower($formAction);
			}

			// make sure the action is allowed. If not revert to default
			if (!in_array($this->action, $this->actions))
				$this->action = 'default';

		// no formAction set: set action to 'default'
		} else {
			$this->action = 'default';
		}

	}
	
	
	/**
	 * Define the view to show
	 * @param string $viewKey
	 * @return void
	 */
	final private function defineView()
	{
		
		// view set, get it.
		if (isset($this->urlParts[1])) {

			// make it ourselves a bit easier to work
			$this->view = strtolower($this->urlParts[1]);

			// make sure the view is allowed. If not, redirect to the module itself (with no view set) with a 404
			if (!in_array($this->view, $this->views))
				PlonkWebsite::redirect('/' . strtolower(MODULE), 404);

		// no formAction set: set view to the default view (the first one defined)
		} else {
			$this->view = $this->views[0];
		}

	}
	
	
	/**
	 * Display it all :-)
	 * @return void
	 */
	final private function display()
	{
			
		// Parse page specific layout into main layout
		$this->mainTpl->assign('pageContent', $this->pageTpl->getContent());
			
		// Output our main layout
		$this->mainTpl->display();
		
	}
	
	/**
	 * The default action
	 * @return void
	 */
	public function doDefault() {
		// no default Action by default (unless you really want to), as the views kick in from here ;-)
	}
	
	
	/**
	 * Executes the Controller
	 * @return void
	 */
	final public function execute()
	{
		
		// If we don't have any views, don't even bother starting this
		if (sizeof($this->views) === 0)	throw new Exception('Cannot initialize module "' . MODULE . '": no views defined; define at least one view');
		
		// Define which action to perform
		$this->defineAction();
		
		// Define which view to show
		$this->defineView();
		
		// load main template
		$this->loadMainTemplate();

		// process the Action
		$this->processAction();
		
		// load page template
		$this->loadPageTemplate();

		// process the View
		$this->processView();
		
		// display
		$this->display();
		
	}
	
	
	/**
	 * Loads the main template
	 * @return void
	 */
	final private function loadMainTemplate()
	{
		
		// load main template
		$this->mainTpl = new PlonkTemplate(PATH_CORE . '/layout/layout.tpl');
		
	}
	
	
	/**
	 * Loads the main template
	 * @return void
	 */
	final private function loadPageTemplate()
	{
		
		// make sure the template exists!
		if (!file_exists(PATH_MODULES . '/' . MODULE . '/layout/' . $this->view . '.tpl'))	throw new Exception('Cannot show view "' . $this->view .' for module ' . MODULE . '": the view tpl file does not exist');
		
		// load main template
		$this->pageTpl = new PlonkTemplate(PATH_MODULES . '/' . MODULE . '/layout/' . $this->view . '.tpl');
		
	}
	
	/**
	 * Processes the action set
	 * @return 
	 */
	final private function processAction()
	{
		
		// action to call
		$toCall = 'do'.ucfirst($this->action);

		// Action doesn't exist
		if (!method_exists($this, $toCall)) {
			throw new Exception('Cannot call action ' . $this->action . ' in module ' . MODULE . ': it does not exist');
		}

		// Action does exist
		$this->{$toCall}();

	}
	
	
	/**
	 * Processes the view set
	 * @return 
	 */
	final private function processView()
	{
		
		// view to show
		$toShow = 'show'.ucfirst($this->view);

		// Action doesn't exist
		if (!method_exists($this, $toShow)) {
			throw new Exception('Cannot show view ' . $this->view . ' in module ' . MODULE . ': it does not exist');
		}

		// Action does exist
		$this->{$toShow}();

	}
	
	
	/**
	 * Returns the version of this class
	 * @return double
	 */
	public static function version() 
	{
		return (float) self::version;
	}
	
	
}