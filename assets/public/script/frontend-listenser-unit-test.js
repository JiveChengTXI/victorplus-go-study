(function($) {
    $(function() {
		const $startWithButton = $('figure.start-with-button')
		const $wpProQuizButton = $('.wpProQuiz_button')
		const onClickQuizStart = (e) => {
			$startWithButton.children('audio').trigger("play")
		}
		if ($startWithButton.length > 0) {
			console.log($startWithButton)
			$wpProQuizButton.on('click', onClickQuizStart)
		}
    })
})(jQuery)