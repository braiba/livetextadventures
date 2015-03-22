var lastTimestamp = 0;

function updateStoryForType(type, id, storyId)
{
	$.ajax({
		url: '/lta/' + type + '/ajax/' + id + '/' + storyId,
		data: {timestamp: lastTimestamp},
		dataType: 'json',
		success: function(data) {
			var messageBox = $('#messageBox');
			if (data['messages'].length != 0) {
				for (var i in data['messages']) {
					var messageData = data['messages'][i];
					messageBox.append('<p class="' + messageData['source'] + '">' + messageData['message'] + '<p>');
				}
				var body = $('body');
				body.scrollTop(body.prop('scrollHeight') - $('document').height());
			}
			lastTimestamp = data['lastTimestamp'];
		}
	});
}

function updateStoryForJumbotron()
{
	$.ajax({
		url: '/lta/jumbotron/ajax/',
		data: {timestamp: lastTimestamp},
		dataType: 'json',
		success: function(data) {
			if (data['messages'].length != 0) {
				for (var i in data['messages']) {
					var messageData = data['messages'][i];				
					var messageBox = $('#messageBox' + messageData['story_id']);
					messageBox.append('<p class="' + messageData['source'] + '">' + messageData['message'] + '<p>');
				}
				$('.messageBox').each(
					function(index){
						var messageBox = $(this);
						messageBox.scrollTop(messageBox.prop('scrollHeight') - messageBox.innerHeight())
					}
				)
			}
			lastTimestamp = data['lastTimestamp'];
		}
	});
}

$('document').ready( function() {
	$('form').submit(
		function(event) {
			var form = $(this);
			var input = form.find('input');
			var type = form.data('type');
			$.post(
				'/lta/' + type + '/message/' + storyId,
				{
					message: input.val()
				}
			);
			input.val('');
			event.preventDefault();
		}			
	)
});