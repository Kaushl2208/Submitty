<?php

namespace app\controllers\admin;

use app\libraries\Core;
use app\controllers\AbstractController;
use app\libraries\Output;
use app\libraries\FileUtils;


class EmailRoomSeatingController extends AbstractController {
	const DEFAULT_EMAIL_SUBJECT = '[Submitty {$course_name}]: Seating Assignment for {$gradeable_id}';
	const DEFAULT_EMAIL_BODY =
'Hello,

Listed below is your seating assignment for the upcoming exam {$gradeable_id} on {$exam_date} at {$exam_time}.

Location: {$exam_building}
Exam Room: {$exam_room}
Zone: {$exam_zone}
Row: {$exam_room}
Seat: {$exam_seat}

Please email your instructor with any questions or concerns.';


	public function __construct(Core $core) {
        parent::__construct($core);
    }

	public function run() {

		switch($_REQUEST['action']) {
				case 'send_email':
						$this->emailSeatingAssignments();
				case 'show_page':
				default:
						$this->renderEmailTemplate();
						break;
		}
		//TODO: check access here
	    // switch ($_REQUEST['page']) {
	    // 	case 'edit_seating_assignment_email':
	    // 		$this->emailSeatingAssignments();
	    // 		break;
			//
			//
	    // }


	}

	private function renderEmailTemplate(){
		$this->core->getOutput()->renderOutput(array('admin', 'EmailRoomSeating'), 'displayPage', EmailRoomSeatingController::DEFAULT_EMAIL_SUBJECT, EmailRoomSeatingController::DEFAULT_EMAIL_BODY);
	}


	public function emailSeatingAssignments() {
		$seating_assignment_subject = $_POST["room_seating_email_subject"];
		$seating_assignment_body = $_POST["room_seating_email_body"];
		
		try {
			$gradeable_id = $this->core->getConfig()->getRoomSeatingGradeableId();
			$course =  $this->core->getConfig()->getCourse();

			$seating_assignments_path = FileUtils::joinPaths($this->core->getConfig()->getCoursePath(), "reports", "seating", $gradeable_id);

			$seating_dir = new \DirectoryIterator($seating_assignments_path);
	        foreach ($seating_dir as $seatingAssignmentFile) {

	        	if (!$seatingAssignmentFile->isDot() && $seatingAssignmentFile->getExtension() === "json") {
		        	$seating_assignment_data = FileUtils::readJsonFile($seatingAssignmentFile->getPathname());

		        	$email_data = [
		                "subject" => $this->replaceSeatingAssignmentDataPlaceholders($seating_assignment_subject, $seating_assignment_data),
		                "body" => $this->replaceSeatingAssignmentDataPlaceholders($seating_assignment_body, $seating_assignment_data)
		            ];

		            $recipient = $seatingAssignmentFile->getBasename('.json');

		        	$this->core->getQueries()->createEmail($email_data, $recipient);
	        	}
	    	}
				$this->core->addSuccessMessage("Seating assignments have been sucessfully emailed!");

		} catch (\Exception $e) {
			$this->core->getOutput()->renderJsonError($e->getMessage());
		}
			return $this->core->redirect($this->core->buildUrl());

	}

	private function replaceSeatingAssignmentDataPlaceholders($seatingAssignmentMessage, $seatingAssignmentData) {

		$seatingAssignmentMessage = str_replace('{$gradeable_id}', $seatingAssignmentData["gradeable"], $seatingAssignmentMessage);
		$seatingAssignmentMessage = str_replace('{$course_name}', $this->core->getConfig()->getCourse(), $seatingAssignmentMessage);
		$seatingAssignmentMessage = str_replace('{$exam_date}', $seatingAssignmentData["date"], $seatingAssignmentMessage);
		$seatingAssignmentMessage = str_replace('{$exam_time}', $seatingAssignmentData["time"], $seatingAssignmentMessage);
		$seatingAssignmentMessage = str_replace('{$exam_building}', $seatingAssignmentData["building"], $seatingAssignmentMessage);
		$seatingAssignmentMessage = str_replace('{$exam_room}', $seatingAssignmentData["room"], $seatingAssignmentMessage);
		$seatingAssignmentMessage = str_replace('{$exam_zone}', $seatingAssignmentData["zone"], $seatingAssignmentMessage);
		$seatingAssignmentMessage = str_replace('{$exam_row}', $seatingAssignmentData["row"], $seatingAssignmentMessage);
		$seatingAssignmentMessage = str_replace('{$exam_seat}', $seatingAssignmentData["seat"], $seatingAssignmentMessage);

		return $seatingAssignmentMessage;
	}



}
