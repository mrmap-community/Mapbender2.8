$(document).ready(function(){
         $('.toggleAppMetadata').click(function(){
		$('#appMetadataContainer').toggle();
		$('#app_metadata').toggleClass('appMetadataContainerOpened');  
		$('#appMetadataContainer').click(function(event){event.stopPropagation();});
	 })
});
