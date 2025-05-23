(function($) {
    let popupShown = false;
    const cooldownKey = 'bop_last_shown';


    $(window).on('scroll', function() {
        if (popupShown) return;


        if ($(window).scrollTop() > bopData.scrollTrigger && bopData.productID !== '0') {

            // Check cooldown from localStorage
            const lastShown = localStorage.getItem(cooldownKey);
            if (lastShown) {
                const daysAgo = (Date.now() - parseInt(lastShown, 10)) / (1000 * 60 * 60 * 24); // convert ms to days
                if (daysAgo < bopData.cooldown) return;
            }


            popupShown = true;

            const popup = $('#bop-popup');
            if ( window.matchMedia("(max-width: 768px)").matches) {
                popup.css({
                    width: 90 + 'vw',
                    height: bopData.height + 'px',
                });
            }
            else {
                popup.css({
                    width: bopData.width + 'px',
                    height: bopData.height + 'px',
                })
            }

            $('#bop-popup').css({
                'background-color': bopData.backgroundColor,
                'color': bopData.textColor
            });


            $('#bop-content').html(`
                <div style="overflow:hidden;">
                    <h4 id="bop-title" style="text-align: center; margin-bottom: 2px">${bopData.title}</h4>
                    <p  style="text-align: center;margin-bottom: 2px; font-weight: bold;  ">${bopData.message.toUpperCase()}</p>
                    <div id="offer">
                      <img id="product_img" src="${bopData.image}" alt="${bopData.title}" ">                 
                      <span style="text-align: center">${bopData.discount}</span>
                    </div>
                    
                    
                    <p id="bop-timer" style="text-align: center; margin-bottom: 2px; margin-top: 10px">
                    <span >00</span> : <span>00</span> : <span id="bop-countdown">${bopData.displayTime}</span>
                    </p>
                    <div id="bop-progress-container" style="width:100%;background:#000000;border-radius:5px;overflow:hidden;margin-top:10px;">
                        <div id="bop-progress-bar" style="height:10px;width:0;;"></div>
                    </div>
                   
                    <button id="bop-checkout">
                    Checkout
                    <img id="btn-img" width="auto" src="https://api.iconify.design/fluent:payment-28-filled.svg?color=%23ffffff" alt="cart"/>
                    </button>
                </div>
            `);

            popup.css({ visibility: 'visible', opacity: '1' });

            $('#bop-checkout').css({backgroundColor: bopData.btnBackgroundColor, color: bopData.btnTextColor})
            $("#bop-checkout").mouseenter(function() {
                $(this).css({backgroundColor: bopData.buttonHoverBgColor, color: bopData.buttonHoverTextColor});
            }).mouseleave(function() {
                $(this).css({backgroundColor: bopData.btnBackgroundColor, color: bopData.btnTextColor});
            });
            $('#btn-img') .attr("src",`https://api.iconify.design/fluent:payment-28-filled.svg?color=%23${bopData.btnTextColor.replace('#','')}`);

            $('#bop-timer span').css('background', bopData.textColor);
            $('#bop-countdown').css('background', bopData.msgColor)
            $('#bop-title').css('color', bopData.textColor + 'BB')
            $('#offer span').css('color', bopData.msgColor)
            $('#bop-progress-bar').css('background', bopData.msgColor)


            let countdown = bopData.displayTime;
            const countdownInterval = setInterval(() => {
                countdown--;
                const progressBar = $('#bop-progress-bar');
                const totalTime = bopData.displayTime;
                $('#bop-countdown').text(`${(countdown<10) ? `0`+countdown : countdown}`);
                progressBar.css('width', (countdown / totalTime * 100) + '%');
                if (countdown <= 0) {
                    clearInterval(countdownInterval);
                    popup.css({ opacity: '0' });
                    setTimeout(() => {
                        popup.css('visibility', 'hidden');
                    }, 500);
                }
            }, 1000);


            $('#bop-close').on('click', function() {
                popup.css({ opacity: '0' });
                setTimeout(() => {
                    popup.css('visibility', 'hidden');
                }, 500);
            });

            $('#bop-checkout').on('click', function() {
                localStorage.setItem(cooldownKey, Date.now().toString());
                document.cookie = "bullet_offer=1; path=/";
                setTimeout(function () {
                    window.location.href = '/?add-to-cart=' + bopData.productID + '&bullet_offer=1';
                }, 300)
            });
        }
    });
})(jQuery);
