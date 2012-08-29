jQuery( document ).ready( function() {

	jQuery( ".section h3" ).click( function() {
		
		jQuery( this ).next().animate({ "height": "toggle" });

	});

});
