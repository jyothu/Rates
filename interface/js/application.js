$(document).ready(function(){
    
	$('.datetimepicker').datetimepicker({
		format: 'GGGG-MM-DD'
	});

    $( "#form" ).on( "submit", function( event ) {
		event.preventDefault();
		getRates( $(this) );
	});

	var getRates = function( myForm ){
		$.ajax({
	        type: "post",
	        url: "getRates.php",
	        data: myForm.serialize(),
	        success: function( data ) {
	            $("#result").html( data );
	        }
	    });
	}

	// Refresh Options drop down dynamically
    $("#service_id").blur(function(){
        callAjaxForOptions( $(this).val() );
    });

    var callAjaxForOptions = function( serviceId ){
 	    $.ajax({
            type: "post",
            url: "getOptions.php",
            data: "service="+serviceId,
            success: function( data ) {
            	if( data == "Invalid Service ID" ){
            	    warning = "<div class='alert alert-warning fade in'>";
            	    warning += "<a href='#'' class='close' data-dismiss='alert'>&times;</a>";
            	    warning += "No Service Option found for the Service ID entered. Please try with another.</div>";
            		$("#alert").html( warning );
                }else{
                    $("#option").html( data );
            	}
            }
        });
    }

});

