<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of StatusController
 *
 * @author Prashant
 */

namespace Management\Controller;

use Management\Form\StatusForm;
use Management\Service\StatusService;
use Zend\Session\Container;
use Zend\View\Model\ViewModel;
use Zend\View\Model\JsonModel;
use ZendTest\View\Helper\Placeholder\StandaloneContainerTest;
use Management\Service\AdminService;

class StatusController extends BaseController{

	private $_session;

    function __construct(){
    	$this->_session = new Container('appl');
    }

    public function indexAction(){
    	$statusId = $this->getRequest()->getQuery('statusId',null);
    	if(is_null($statusId))
    		$statusId = (is_null($statusId)) ? $this->getRequest()->getPost("statusId",null) : null;
    	$this->getServiceLocator()->get('viewhelpermanager')->get('HeadScript')->appendFile('/js/ckeditor/ckeditor.js');
        $statusForm = new StatusForm($this->getEntityManager());
        $statusForm->add(array('name' => 'statusId','type' => 'Hidden','attributes' =>  array('id' => 'statusId','value'=>$statusId)));
        if ($this->getRequest()->isPost()){
        	$post = $this->getRequest()->getPost();
        	$statusForm->setData($post);
        	if ($statusForm->isValid()){
        		$serviceStatus = new StatusService($this->getEntityManager());
        		if($post['submit'] == 'Save'){
        			$response = $serviceStatus->saveStatus($post);
        			$this->redirectTo($response);
        		}elseif($post['submit'] == 'Edit'){
        			$data = $serviceStatus->editStatus($post);
        			$this->redirect()->toUrl('/management/status/report');

        		}
        	}
        }elseif(!is_null($statusId)){
        	$elements = $statusForm->getElements();
        	$elements["submit"]->setAttribute("value","Edit");
        	$adminService = new AdminService($this->getEntityManager());
        	$teamAbbrArr = $adminService->getTeamDropdown();	//get Team abbrevations array
        	// display for edit status
        	$serviceStatus = new StatusService($this->getEntityManager());
        	$userReport = $serviceStatus->getUserReport($this->_session->userId,null,null,$statusId);
        	foreach ($userReport as $report){
        		foreach ($report as $key => $eachStatus){
        			if($key == 'report'){
	        			foreach ($eachStatus as $statusDescription){
	        				$data = (array)$statusDescription;
	        				list($abbr, $tktNo) = explode("-",$statusDescription->jiraTicketId);
	        				$data['ticketType']= array_search($abbr, $teamAbbrArr);
	        				$data['ticketNumber'] = $tktNo;
	        				$statusForm->populateValues($data);
	        			}
        			}
        		}
        	}

        }
        return new ViewModel(array('statusForm'=>$statusForm));
    }

    public function reportAction(){
    	$serviceStatus = new StatusService($this->getEntityManager());
    	$userReport = $serviceStatus->getUserReport($this->_session->userId);
    	return new ViewModel(array('reportObj' => $userReport));
    }

    public function viewallreportAction(){
    	$serviceStatus = new StatusService($this->getEntityManager());
    	$reports = $serviceStatus->getAllReports();
    	return new ViewModel(array('teamUser' => json_decode(json_encode($reports, true))));
    }

    public function getUserReportAction(){
    	$request = $this->getRequest();
    	if ($request->isXmlHttpRequest()){
    		$allParams = $this->params()->fromQuery();
    		$serviceStatus = new StatusService($this->getEntityManager());
    		$userId = $request->getPost('userId');
    		$reportDate = $request->getPost('reportDate');
    		$userReport = $serviceStatus->getUserReport($userId, $reportDate);
    	}else {
    		$serviceStatus = new StatusService($this->getEntityManager());
    		$allParams = $this->params()->fromQuery();
    		$userId = $allParams['userId'];
    		$reportDate = $request->getPost('reportDate');
    		$userReport = $serviceStatus->getUserReport($userId);echo "<pre>";print_r($userReport);exit;
    	}
    	return new JsonModel(array('userReport'=>$userReport));
    }

    public function deletestatusAction(){
    	$statusId = $this->getRequest()->getQuery('statusId');
    	$serviceStatus = new StatusService($this->getEntityManager());
    	$serviceStatus->deleteStatus($statusId);
    	$this->redirect()->toUrl('/management/status/report');
    }

}

?>
