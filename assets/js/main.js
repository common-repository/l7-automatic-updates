jQuery(document).ready(function($) {
	$(':checkbox').checkboxpicker();

	/**
     * Set the display of the plugin options depending on the checkbox.
     */
    if( $("input[name='l7wau_settings_group[plugins]']").is(':checked')) {
        document.getElementById("l7wau-hide-js").style.display = "none"; 
    } else {
        document.getElementById("l7wau-hide-js").style.display = "block";
    }

    /**
     * Set the bottom plugin options to toggle when all plugins is clicked
     */
    $( "input[name='l7wau_settings_group[plugins]']" ).next( '.btn-group' ).children( 'a' ).on('click', function(e){
        var checkbox = $( this ).text();
        if (checkbox == 'No'){ 
            document.getElementById("l7wau-hide-js").style.display = "block"; 
        }
        if (checkbox == 'Yes'){
            document.getElementById("l7wau-hide-js").style.display = "none"; 
        }
    })
});