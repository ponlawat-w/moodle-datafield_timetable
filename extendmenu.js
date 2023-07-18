require(['jquery'], $ => {
    $(document).ready(() => {
        const getboostitem = (text, url) => $('<li>').addClass('nav-item').html(
            $('<a>').addClass('nav-link')
                .attr('href', url)
                .attr('title', text)
                .html(text)
        );

        const getclassicitem = (text, url) => $('<a>').addClass('dropdown-item menu-action')
            .attr('href', url)
            .attr('role', 'menuitem')
            .html(
                $('<span>').addClass('menu-action-text').html(text)
            );

        const initclassic = () => {
            const $actionmenu = $('.moodle-actionmenu');
            if (!$actionmenu.length) {
                return;
            }
            const $dropdownmenu = $actionmenu.find('.dropdown-menu');
            for (const item of DATAFIELD_TIMETABLE_EXTENDMENU) {
                $dropdownmenu.append(getclassicitem(item.text, item.url));
            }
        };

        const init = () => {
            if (!DATAFIELD_TIMETABLE_EXTENDMENU) {
                return;
            }

            const $allnavtabs = $('div[role=main]').find('.nav.nav-tabs');
            if (!$allnavtabs.length) {
                return initclassic();
            }
            const $navtabs = $($allnavtabs[0]);
            for (const item of DATAFIELD_TIMETABLE_EXTENDMENU) {
                $navtabs.append(getboostitem(item.text, item.url));
            }
        };
        init();
    });
});
