jQuery(document).ready(function() {
	
	'use strict';
	
	/* Sticky Header */
	jQuery(window).scroll(function() {
		if (jQuery(window).scrollTop() > 130) {
			jQuery('.top').addClass('sticky');
			jQuery('.top').removeClass('non_sticky');
		}
		else {
			jQuery('.top').removeClass('sticky');
			jQuery('.top').addClass('non_sticky');
		}
	});
	
/*********************** Mmenu *********************/

	  /*$("#mobile_menu").mmenu({
		  extensions: ["pageshadow" , "border-full" , "effect-listitems-slide" ],
		  offCanvas: {position: "right"}
	  }, {
		 // configuration
		 classNames: {
			fixedElements: {
			   fixed: "abc"
			}
		 }
	  }); */	
	
/*********************** Waves *********************/
	
	/*Waves.init();
	Waves.attach('.os_flat_btn', ['waves-button']);	*/
	
		
		
		
		
		
});