<?php
	// Require relevent information for settings.config.inc.php, including functions and database access
	require_once("../includes/settings.config.inc.php");

	// Set $page_name so that the title of each page is correct
	$page_name = PAGENAME_CONTACTS;
	
	// Check if $user is authenticated
	if(!$user->authenticated) {
		$user->logout('not_authenticated');
	}; // Close if(!$user->authenticated)
		
	// If the value of i in GET exists
	if(isset($_GET["i"])) {

		// Find contact in database
		$contact = new Contact($_GET['i']);
		
		// If a contact is found in the database
		if($contact->found) {
			
			// Set page name as contact could be found
			$subpage_name = $contact->full_name . ' - ' . PAGENAME_CONTACTSDELETE;
			
			// Obtain a CSRF token to be used to prevent CSRF - this is stored in the $_SESSION
			$csrf_token = CSRF::get_token();
			
			// Check that the user has submitted the form
			if(isset($_POST["submit"]) && $_POST["submit"] == "submit") {
				// Ensure that the user actually wants to delete the user
				if(isset($_POST["confirm_delete"])) {
					// Validate all fields and ensure that required fields are submitted
				
					// Initialise the $errors are where errors will be sent and then retrieved from
					$errors = array();
					
					// Check that the submitted CSRF token is the same as the one in the $_SESSION to prevent cross site request forgery
					if(!CSRF::check_token($_POST['csrf_token']))									{ $errors[] = $validation['invalid']['security']['csrf_token']; };
					
					// If no errors have been found during the field validations
					if(empty($errors)) {
						// Delete the contact
						$result = $contact->delete();

						// Confirm that the result was successful, and that only 1 item was deleted
						if($result) {
							// Contact successfully deleted
							$session->message_alert($notification["contact"]["delete"]["success"], "success");
							// Log action of database entry success
							$log = new Log('contact_delete_success', 'Contact of ' . $contact->full_name . ' from ' . $contact->single['address_town'] . ' was deleted.');
							// Redirect the user
							Redirect::to(PAGELINK_INDEX);
						} else {
							// Contact failed to be deleted
							$session->message_alert($notification["contact"]["delete"]["failure"], "danger");
							// Log action of database entry failing
							// Create new Log instance, and log the action to the database
							$log = new Log('contact_delete_failed', 'database');
						};
					} else {
						// Form field validation has failed - $errors array is not empty
						// If there are any error messages in the $errors array then display them to the screen
						$session->message_validation($errors);
						// Log action of failing form process
						// Create new Log instance, and log the action to the database
						$log = new Log('contact_delete_failed', 'Failed contact delete due to form validation errors.');
					};

				} else {
					// User did not confirm that they would like to delete the contact
					// Set a failure session message and redirect them to view the contact
					$session->message_alert($validation["field_required"]["contact"]["confirm_delete"], "danger");
					// Log action of failing to confirm delete
					// Create new Log instance, and log the action to the database
					$log = new Log('contact_delete_failed', 'User did not confirm that they wanted to delete the contact.');
					// Redirect the user
					Redirect::to(PAGELINK_CONTACTSVIEW . '?i=' . urlencode($contact->single['contact_id']));
				};

			}; // User has not submitted the form - do nothing

			// User has accessed the page and not sumitted the form
			// Create new Log instance, and log the page view to the database
			$log = new Log('view');

		} else {
			// Contact could not be found in the database
			// Send session message and redirect
			$session->message_alert($notification["contact"]["delete"]["not_found"], "danger");
			// Set $subpage_name so that the title of each page is correct - contact couldn't be found
			$subpage_name = 'Contact Not Found - ' . PAGENAME_CONTACTSDELETE;
			// Create new Log instance, and log the action to the database
			$log = new Log('not_found');
			// Redirect the user
			Redirect::to(PAGELINK_INDEX);
		};

	} else {
		// Value of i in GET doesn't exist, send session message and redirect
		$session->message_alert($notification["contact"]["delete"]["not_found"], "danger");
		// Set $page_name so that the title of each page is correct - GET value not correct
		$subpage_name = 'Invalid GET Value - ' . PAGENAME_CONTACTSDELETE;
		// Create new Log instance, and log the action to the database
		$log = new Log('not_found');
		// Redirect the user
		Redirect::to(PAGELINK_INDEX);
	};

	// Require head content in the page
	require_once("../includes/layout.head.inc.php");
	// Requre navigation content in the page
	require_once("../includes/layout.navigation.inc.php");

?>
			<!-- CONTENT -->
			<?php $session->output_message(); ?>
			
			<h3>WARNING</h3>
			<p><strong>This process is <u>IRREVERSIBLE</u>. Once a contact has been deleted the only way to restore them to the contact list is by manually re-adding.</strong></p>
			<p>Please confirm that you would like to <strong>permanently delete</strong> <?php echo $contact->full_name; ?> from the system.</p>

			<form class="form-horizontal" action="" method="post">

				<div class="checkbox">
					<label>
						<input type="checkbox" name="confirm_delete"> Yes, I am sure that I want to <strong>permanently delete</strong> <?php echo $contact->full_name; ?>
					</label>
				</div>
				
				<input type="hidden" name="csrf_token" value="<?php echo htmlentities($csrf_token); ?>"/>

				<hr>

				<div >
					<button type="submit" name="submit" value="submit" class="btn btn-danger">Delete Contact</button>
				</div>
			</form>
			<!-- /CONTENT -->

<?php
	// Requre footer content in the page, including any relevant scripts
	require_once("../includes/layout.footer.inc.php");
?>