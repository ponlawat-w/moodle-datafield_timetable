require(['jquery'], $ => {
    $(document).ready(() => {
        const getnavitem = (text, url) => $('<li>').addClass('nav-item').html(
            $('<a>').addClass('nav-link')
                .attr('href', url)
                .attr('title', text)
                .html(text)
        );

        const init = () => {
            if (!DATAFIELD_TIMETABLE_EXTENDMENU) {
                return;
            }

            const $allnavtabs = $('div[role=main]').find('.nav.nav-tabs');
            if (!$allnavtabs.length) {
                return;
            }
            const $navtabs = $($allnavtabs[0]);
            for (const item of DATAFIELD_TIMETABLE_EXTENDMENU) {
                $navtabs.append(getnavitem(item.text, item.url));
            }
        };
        init();
    });
});
