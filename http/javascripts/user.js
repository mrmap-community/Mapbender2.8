var timeoutDelay = 250;

$(document).ready(function() {
    $("input#find_user").keyup(function() {
        var input = $(this);
        var target = $('#' + input.attr('data-target'));
        var targetType = input.attr('data-target-type');
        var ownerCheck = input.attr('owner-check');
            
        if(typeof timeoutId !== 'undefined') {
            window.clearTimeout(timeoutId);
        }
        
        timeoutId = window.setTimeout(function() {
            timeoutId = undefined;
            
            if(input.val() == "") {
                return false;
            }

            $.ajax({
                url: "../php/user.php",
                data: {
                    "searchterm" : input.val(),
                    "userCheck" : ownerCheck
                },
                type: "post",
                dataType: "json",
                success: function(data) {

                    
                    if(targetType === 'select') {
                        target.children().remove();
                        
                        if(input.attr('data-target-new') && input.attr('data-target-new') === 'true') {
                            target.append(
                                $('<option>')
                                    .attr('value', 'new')
                                    .text('NEW...')
                            );
                        }
                        
                        for(var i=0; i<data.users.length; i++) {
                            target.append(
                                $('<option>')
                                    .attr('value', data.users[i].id)
                                    .attr('title', data.users[i].id + ": " + data.users[i].name + ' (' + data.users[i].email + ")")
                                    .text(data.users[i].login)
                            );
                        }
                        target.prev().text("USER (" + data.limit + " von "  + data.hits + "):");
               
                    }
                }
                
            });
            
            return true;
        }, timeoutDelay);
    });
});