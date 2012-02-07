/**
 * Stream Test Core Javascripts.
 * 
 * Ben Constable http://www.benconstable.co.uk
 */

(function($) {
	
	// Setup Bootstrap functionality
	$(".dropdown-toggle").dropdown();
	$(".alert-message").alert();
	
	// Twitter add account show / hide
	$(".twitter-add-button").bind("click", function(e) {
		e.preventDefault();
		$(".twitter-add-container").show();
	});
	
	$(".twitter-add-close").bind("click", function(e) {
		e.preventDefault();
		$(".twitter-add-container").hide();
	});
	
}(jQuery));


