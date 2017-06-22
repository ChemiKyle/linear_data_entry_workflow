<?php
return function($project_id) {

  $URL = $_SERVER['REQUEST_URI'];

  //check if we are on the right page
  if(preg_match('/DataEntry\/record_home/', $URL) !== 1) {
    return;
  }

  //Read configuration data from redcap_custom_project_settings data store
  $my_extension_name = 'reveal_forms_in_order';
  require_once "../../plugins/custom_project_settings/cps_lib.php";
  $cps = new cps_lib();
  $my_settings = $cps->getAttributeData($project_id, $my_extension_name);
  $project_json = json_decode($my_settings, true);

  //get form names used internally by REDCap
  $forms = array_keys(REDCap::getInstrumentNames());

  //use form names to contruct complete_status field names
  foreach($forms as $index => $form_name) {
    $forms[$index] = $form_name . '_complete';
  }

  /*request data as an array to get corresponding record ids and events with
  complete forms */
  $completed_forms = REDCap::getData($_GET['pid'], 'array', $_GET['id'], $forms);

  ?>

    <script>
      var json = <?php echo json_encode($project_json) ?>;
      var completedForms = <?php echo json_encode($completed_forms) ?>;

      /*converts a pageName on a link to the corresponding form's complete_status
      field name*/
      function pageToFormComplete(pageName) {
        return pageName + '_complete';
      }

      function disableForm(cell) {
          cell.style.pointerEvents = 'none';
          cell.style.opacity = '.1';
      }

      function getQueryString(url) {
        url = decodeURI(url);
        return url.match(/\?.+/)[0];
      }

      function getQueryParameters(url) {
        var parameters = {};
        var queryString = getQueryString(url);
        var reg = /([^?&=]+)=?([^&]*)/g;
        var keyValuePair;
        while(keyValuePair = reg.exec(queryString)) {
          parameters[keyValuePair[1]] = keyValuePair[2];
        }
        return parameters;
      }

      function run(){
        var $rows = $('#event_grid_table tbody tr');
        var previousFormCompleted = true;

        //start at 1 to avoid disabling "Data Collection Instrument" column
        for(var i = 1; i < $rows[0].cells.length; i++) {

          //stop at $rows.length - 1 to avoid "delete all data on event" row
          for(var j = 0; j < $rows.length - 1; j++) {

            //check if cell has a link in it
            if($rows[j].cells[i].getElementsByTagName('a')[0] === undefined) {
              continue;
            }

            //if last form was incomplete disable every form after it
            if(!previousFormCompleted) {
              disableForm($rows[j].cells[i]);
              continue;
            }

            var link = $rows[j].cells[i].getElementsByTagName('a')[0].href;
            var param = getQueryParameters(link);

            /*Need to check if event_id is defined in completedForms first because
            js crashes if it has to check the property of an undefined variable*/
            if(completedForms[param.id][param.event_id] === undefined ||
               completedForms[param.id][param.event_id][pageToFormComplete(param.page)] === undefined ||
               completedForms[param.id][param.event_id][pageToFormComplete(param.page)] !== "2") {
                 previousFormCompleted = false;
                 continue;
            }
          }
        }
      }

    $('document').ready(function() {
      //run the hook
      run();
    });

    </script>

<?php
}
?>
