<?php


function get_abc_data($search_type, $specialization, $term)
{


    /* If search type is xyz or not
    ============================================= */

    if ($search_type == "xyz") {

        /* xyz Search
        ============================================= */

        $url = 'http://url-of-api-json-endpoints/api/xyz/' . $term;
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);
        $http = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $json = @json_decode($response, TRUE);
        if ($http == 200) {
            return $json;
        }


    } else {


        /* Search by specialization and term
        ============================================= */

        $url = 'http://url-of-api-json-endpoints/api/abcterm/' . $specialization . '/' . get_current_term() . '/all';
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);
        $http = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $json = @json_decode($response, TRUE);
        $courses = array();
        if ($http == 200) {
            foreach ($json as $key => $term) {
                foreach ($term as $course) {
                    array_push($courses, $course);
                }
            }
            return $json;
        }
    }
}




function get_built_abc_course_list()
{

    $search_type = $_POST["search-type"];
    $specialization = $_POST["specialization"];
    $term = $_POST["term"];




    /* Results
    ============================================= */

    if (isset($search_type) && isset($term)) {


        /* Master Array
        ============================================= */

        $abc_data = get_abc_data($search_type, $specialization, $term); ;
        
        if($_POST["search-type"] == "abc-enrolled") {   
            $loop_data = $abc_data[strtoupper($term)];
        } else {
            $loop_data = $abc_data;
        }



    

        if(count($loop_data) !== 0) {

            $sortArray = array();
            $collectedIds = array();
            $course_ids = array();



            /* Sort the $loop_data by the "course_name"
             * this code was found https://www.php.net/manual/en/function.ksort.php#98465 in the first comment under "User Contributed Notes"
             * adjust the DESC to ACS
             * 
             * Additionally push every course id to a new array
            ============================================= */
            foreach($loop_data as $course){
                array_push($course_ids, $course['course_id']);

                foreach($course as $key=>$value){
                    
                    if(!isset($sortArray[$key])){
                        $sortArray[$key] = array();
                    }
                    $sortArray[$key][] = $value;
                }
            }

            $orderby = "course_name"; //change this to whatever key you want from the array
            array_multisort($sortArray[$orderby],SORT_ASC,$loop_data);
            



            /* Sort $course_ids and run it through array_count_values();
            ============================================= */
            sort($course_ids);
            $course_ids_vals = array_count_values($course_ids);



            /* Master Loop
            ============================================= */

            $last_dupped_id = '';
            foreach ($loop_data as $course) {

                // get course id into var
                $c_id = $course['course_id'];

                // get count of how many times said course id is in $course_ids_vals
                $dup_count = $course_ids_vals[$c_id];
                
                
                /* Check if dup course id or not
                 * 
                 *  If the course is in course_ids_vals > 1 then it is a dup
                 *  * set var $d as a counter
                 * 
                 *  * If it's not the first instance of the course id in $course_ids:
                 *  *   * get term info into $new_term array
                 *  *   * push $new_term array to $new_term_array
                 *  *   * add to $d for every dup
                 *  *   * if $d == $dup_count echo out abc-card with the final $new_term_array array to be looped through within the card
                 *  * If it's the first instance of the course id in $course_ids:
                 *  *   * get term info into $new_term array
                 *  *   * push $new_term array to $new_term_array
                 *  * track last used id to know if it's a dup again or a new course that is dup'ed
                 * 
                 *  If the course is not in $course_ids_vals:
                 *  * get term info into $new_term array
                 *  * push $new_term array to $new_term_array
                 *  * echo abc card
                ============================================= */
                if (count(array_keys($course_ids, $c_id)) > 1) {
                    $d = 1;

                    if( $last_dupped_id == $c_id ) {
                        $new_term = array(
                            'course_id' => $c_id,
                            'term' => $course['term'],
                            'pres_format' => $course["presentation_format"],
                            'course_format' => $course["course_format"],
                            'location' => $course["location"],
                            'section_letter' => $course["section_letter"],
                            'schedule' => $course["schedule"]
                        );

                        $new_term_array[] = $new_term;
                        $d++;

                        if( $d == $dup_count ) {
                            include(locate_template("/partials/blocks/abc-card.php"));
                        }
                    } else {
                        $new_term_array = array();

                        $new_term = array(
                            'course_id' => $c_id,
                            'term' => $course['term'],
                            'pres_format' => $course["presentation_format"],
                            'course_format' => $course["course_format"],
                            'location' => $course["location"],
                            'section_letter' => $course["section_letter"],
                            'schedule' => $course["schedule"]
                        );

                        $new_term_array[] = $new_term;
                    }

                    $last_dupped_id = $c_id;
                } else {
                    $new_term_array = array();

                    $new_term = array(
                        'course_id' => $c_id,
                        'term' => $course['term'],
                        'pres_format' => $course["presentation_format"],
                        'course_format' => $course["course_format"],
                        'location' => $course["location"],
                        'section_letter' => $course["section_letter"],
                        'schedule' => $course["schedule"]
                    );

                    $new_term_array[] = $new_term;
                    include(locate_template("/partials/blocks/abc-card.php"));
                }
            }
    

        }



    }


    wp_die();
}



add_action('wp_ajax_abc_filter', 'get_built_abc_course_list');
add_action('wp_ajax_nopriv_abc_filter', 'get_built_abc_course_list');