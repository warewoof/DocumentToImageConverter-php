<?php

    /******     UPDATE THESE PATHS TO MATCH YOUR SYSTEM     *****/

    /* LibreOffice path to folder where the 'suoffice' executable resides */
    $libre_office_path = '/Applications/LibreOffice.app/Contents/MacOS/';  

    /* ImageMagick path to folder where 'convert' executable resides */
    $image_magick_path = '/opt/ImageMagick/bin/';  

    /* Ghostscript path to folder where 'gs' executable resides */
    $ghostscript_path = '/opt/Ghostscript/bin/';  


    /**************************************************************/

    $upload_folder_name = 'upload/';
    $upload_path = dirname(__FILE__).'/'.$upload_folder_name;
    $temp_file_prefix = 'tmpFile';
    $allowed_extensions =  array('doc','docx','ppt','pptx','xls','xlsx','pdf');
    $output_image_density = 300;
    $output_image_format = 'jpg';
?>
<!DOCTYPE html>
<html lang="en">
    <head>
        <script type="text/javascript" src="support/js/jquery-1.9.1.min.js"></script>        
        <link rel="stylesheet" href="support/css/jquery.fancybox.css?v=2.1.5" type="text/css" media="screen" />
        <script type="text/javascript" src="support/js/jquery.fancybox.js?v=2.1.5"></script>
        <link rel="stylesheet" href="support/css/jquery.fancybox-buttons.css?v=1.0.5" type="text/css" media="screen" />
        <script type="text/javascript" src="support/js/jquery.fancybox-buttons.js?v=1.0.5"></script>
        <link rel="stylesheet" href="support/css/jquery.fancybox-thumbs.css?v=1.0.7" type="text/css" media="screen" />
        <script type="text/javascript" src="support/js/jquery.fancybox-thumbs.js?v=1.0.7"></script>
        <link href="support/css/custom.css" rel="stylesheet">
    </head>

    <body>
        <?php 
            $upload_HTML_block = '<br/><form enctype="multipart/form-data" action="'.basename(__FILE__).'" method="post">
                <input type="hidden" name="MAX_FILE_SIZE" value="20000000" />
                Choose a document to convert: <input name="uploaded_file" type="file" />
                <input class="myButton" type="submit" value="Convert" />
            </form> 
            <br/>';
        ?>
        <?php if (empty($_FILES)) { 
            echo $upload_HTML_block;
        } else { 
            
            /******     FILE UPLOAD     *****/
            
            $time_start = microtime(true); 
            if((!empty($_FILES["uploaded_file"])) && ($_FILES['uploaded_file']['error'] == 0)) {
                $file_name = basename($_FILES['uploaded_file']['name']);
                $ext = pathinfo($file_name, PATHINFO_EXTENSION);
                $file_uploaded = false;
                $error_message = "";
                if(in_array($ext,$allowed_extensions) ) {
                    if ($_FILES["uploaded_file"]["size"] < 20000000) {
                        if (!file_exists($upload_path)) {
                            mkdir($upload_path, 0777, true);
                        }
                        $new_name = $upload_path.$temp_file_prefix.date('YmdHis').'.'.pathinfo($file_name, PATHINFO_EXTENSION);
                        if (!file_exists($new_name)) {
                            if ((move_uploaded_file($_FILES['uploaded_file']['tmp_name'],$new_name))) {
                                echo 'Current script path: '.exec('pwd').'<br/>';
                                echo "File saved as: ".$new_name."<br/>";
                                $originalfile_name = basename($file_name, ".".$ext); 
                                $file_uploaded = true;
                            } else {
                                $error_message = "Error: A problem occurred during file upload!<br/>";
                            }
                        } else {
                            $error_message = "Error: File ".$_FILES["uploaded_file"]["name"]." already exists<br/>";
                        }
                    } else {
                        $error_message = "Error: Only documents under 200MB are accepted for upload<br/>";    
                    }
                } else {
                    $error_message = "Error: Only ".implode(", ", $allowed_extensions)." files are accepted for upload<br/>";
                }
            } else {
                $error_message = "Error: No file uploaded<br/>";   
            }

            if (!$file_uploaded) {
                echo $upload_HTML_block;
                echo $error_message;
            } else {
                if ($ext != 'pdf') {

                    /******     PDF CONVERSION     *****/

                    $conversion_start = microtime(true); 
                    echo "<br/>Converting to PDF...<br/>";                    
                    echo "Calling shell command...<br/>";
//                    $exec_string = $libre_office_path.'soffice --headless --convert-to pdf '.$new_name.' --outdir '.pathinfo($new_name, PATHINFO_DIRNAME).'/';
                    $exec_string = $libre_office_path.'soffice --headless "-env:UserInstallation=file:///tmp/LibreOffice_Conversion_${USER}" --convert-to pdf:writer_pdf_Export --outdir '.pathinfo($new_name, PATHINFO_DIRNAME).'/ '.$new_name;
                    echo $exec_string.'<br/>';
                    $outPT="";
                    exec($exec_string, $out, $ret).'<br/>';
                    if ($ret){
                        echo "PDF conversion fail<br/>";
                        print_r($out);
                    }else{
                        echo "PDF conversion success<br/>";
                    }
                    echo '<b>Conversion Time:</b> '.(microtime(true)-$conversion_start).' seconds<br/>';
                }
                
                /******     IMAGE CONVERSION     *****/

                $conversion_start = microtime(true); 
                echo "<br/>Converting to ".strtoupper($output_image_format)."...<br/>";
                putenv("PATH=".$ghostscript_path);    /* this command is important to let script know where GS resides */
                $fileNoExt = basename($new_name, ".".$ext); 
                
                $exec_string = $image_magick_path.'convert -density '.$output_image_density.' '.pathinfo($new_name, PATHINFO_DIRNAME).
                            '/'.$fileNoExt.'.pdf '.pathinfo($new_name, PATHINFO_DIRNAME).
                            '/'.$fileNoExt.'-%d.'.$output_image_format;
                // resize option is flaky with jpg images

                echo "Calling shell command...<br/>";
                echo $exec_string.'<br/>';
                exec($exec_string, $out, $ret);
                if ($ret){
                    echo "Image conversion fail<br/>";
                    print_r($out);
                }else{
                    echo "Image conversion success<br/>";
                }

                echo '<b>Conversion Time:</b> '.(microtime(true)-$conversion_start).' seconds<br/>';

                $time_end = microtime(true);

                $image_array = array();
                $dir =  $upload_path;
                $prefix = $fileNoExt;
                chdir($dir);
                $matches = glob("$prefix*.".$output_image_format);
                if(is_array($matches) && !empty($matches)){
                    foreach($matches as $match){
                        $image_array[] = $match;
                    }
                }
                $execution_time = ($time_end - $time_start);
                $image_count = count($image_array);
                
                echo "<br/>Total pages converted: ".$image_count."<br/>";
                echo '<b>Total Execution Time:</b> '.$execution_time.' seconds<br/>';

                /******     IMAGE GALLERY     *****/

                for ($i=0; $i<$image_count; ++$i) {
                    $image_link = $upload_folder_name.$prefix.'-'.$i.'.'.$output_image_format;
                    echo '<a class="fancybox" rel="group" href="'.$image_link.'" title="'.$originalfile_name.' - page '.($i+1).'"><img src="support/thumbgen.php?image=../'.$image_link.'" alt="" /></a>';
                }
                
                ?>
                    <br/>
                    <br/>
                    <form action="<?php basename(__FILE__); ?>">
                        <input class="myButton" type="submit" value="Convert another file">
                    </form>
            <?php } ?>
        <?php } ?>

    <script type="text/javascript">
        $(document).ready(function() {
            var expand_flag = false,
                page_count = <?php echo $image_count; ?>;

            $(".fancybox").attr('rel', 'gallery').fancybox({
                beforeShow: function () {
                    console.log('Started!');
                    /* Disable right click */
                    $.fancybox.wrap.bind("contextmenu", function (e) {
                            return false; 
                    });
                },
                padding : 0,
                helpers : {
                    thumbs : {
                        width: 50,
                        height: 50
                    },
                    buttons : {},
                    title: {
                        type: 'outside',
                        position: 'bottom'
                    }
                },
                nextEffect: 'none',
                prevEffect: 'none',
                afterLoad: function(current, previous) {
                    console.log('afterLoad');
                    console.info( 'Current: ' + current.href );        
                    console.info( 'Previous: ' + (previous ? previous.href : '-') );
                    
                    if (previous) {
                        console.info( 'Navigating: ' + (current.index > previous.index ? 'right' : 'left') );     
                          
                    }
                    if (previous) {
                        console.log('previous fitToView was ' + previous.fitToView);
                        if (!previous.fitToView) {
                            console.log('expand!');
                            expand_flag = true;
                        } else {
                            expand_flag = false;
                        }
                    }
                    
                },
                afterShow    :   function() {
                    console.log('afterShow!');
                    if (expand_flag) {
                        parent.$.fancybox.toggle();    
                        //parent.$.fancybox.jumpto(3); 
                    }
                    
                },
                afterClose    :   function() {
                    console.log('afterClose!');
                    expand_flag = false;
                }
            });

            

            
        });
    </script>

    </body>
</html>