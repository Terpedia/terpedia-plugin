jQuery(document).ready(function($) {
    'use strict';
    
    // Update selected members display when checkboxes change
    function updateSelectedMembers() {
        var selectedMembers = [];
        $('input[name="podcast_speakers[]"]:checked').each(function() {
            var memberName = $(this).siblings('.member-name').text();
            selectedMembers.push(memberName);
        });
        
        var selectedList = $('#selected-members-list');
        selectedList.empty();
        
        if (selectedMembers.length > 0) {
            selectedMembers.forEach(function(memberName) {
                selectedList.append('<span class="selected-member-tag">' + memberName + '</span>');
            });
        } else {
            selectedList.append('<span class="description">No members selected</span>');
        }
    }
    
    // Bind change event to all speaker checkboxes
    $('input[name="podcast_speakers[]"]').on('change', updateSelectedMembers);
    
    // Initial update
    updateSelectedMembers();
    
    // Handle conversation generation
    $('#generate_conversation').on('click', function() {
        var speakers = [];
        $('input[name="podcast_speakers[]"]:checked').each(function() {
            speakers.push($(this).val());
        });
        var topic = $('#podcast_topic').val();
        var duration = $('#podcast_duration').val();
        
        if (speakers.length === 0) {
            alert('Please select at least one speaker.');
            return;
        }
        
        if (!topic) {
            alert('Please enter a topic for the conversation.');
            return;
        }
        
        var $button = $(this);
        var $status = $('#conversation_status');
        
        $status.html('Generating conversation...');
        $button.prop('disabled', true);
        
        $.ajax({
            url: terpediaPodcastAdmin.ajaxurl,
            type: 'POST',
            data: {
                action: 'generate_podcast_conversation',
                nonce: terpediaPodcastAdmin.nonce,
                speakers: speakers,
                topic: topic,
                duration: duration,
                post_id: $('#post_ID').val()
            },
            success: function(response) {
                if (response.success) {
                    $('#podcast_conversation').val(response.data.conversation);
                    $status.html('Conversation generated successfully!');
                    $status.css('background', '#d4edda');
                    $status.css('border-color', '#c3e6cb');
                    $status.css('color', '#155724');
                } else {
                    $status.html('Error: ' + response.data);
                    $status.css('background', '#f8d7da');
                    $status.css('border-color', '#f5c6cb');
                    $status.css('color', '#721c24');
                }
            },
            error: function() {
                $status.html('Error generating conversation');
                $status.css('background', '#f8d7da');
                $status.css('border-color', '#f5c6cb');
                $status.css('color', '#721c24');
            },
            complete: function() {
                $button.prop('disabled', false);
            }
        });
    });
    
    // Auto-generate topic description based on title
    $('#title').on('blur', function() {
        var title = $(this).val();
        var topicField = $('#podcast_topic');
        
        if (title && !topicField.val()) {
            // Simple topic generation based on title
            var topic = title.replace(/[Pp]odcast|[Ee]pisode|[Tt]erpedia/gi, '').trim();
            if (topic) {
                topicField.val(topic);
            }
        }
    });
    
    // Character counter for conversation textarea
    $('#podcast_conversation').on('input', function() {
        var length = $(this).val().length;
        var $counter = $('#conversation-char-count');
        
        if ($counter.length === 0) {
            $counter = $('<div id="conversation-char-count" class="description"></div>');
            $(this).after($counter);
        }
        
        $counter.text('Characters: ' + length);
    });
    
    // Initialize character counter
    $('#podcast_conversation').trigger('input');
});