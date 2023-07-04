(function($) {
    $(function() {
        const $ameliaContainer = $('#amelia-v2-booking-1000')
        if ($ameliaContainer.length === 0) {
            return ;
        }
        const changeFileAccept = (e) => {
            console.log('changeFileAccept')
            const $this = $(this)
            const $inputFile = $this.find('input[type=file]')
            $inputFile.attr('accept', '.pdf')
        }
        $ameliaContainer.on('mouseenter', '#amelia-container .am-attachment', changeFileAccept)
    })
})(jQuery)