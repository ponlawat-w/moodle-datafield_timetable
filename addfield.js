require(['jquery'], $ => {
    $(document).ready(() => {
        const $timetables = $('.datafield_timetable-timetable');
        for (let t = 0; t < $timetables.length; t++) {
            const $timetable = $($timetables[t]);
            const fieldId = $timetable.attr('data-fieldid');

            const $tbody = $timetable.find('.datafield_timetable-tbody');
            const $template = $tbody.find('.datafield_timetable-slot_template');
            $template.find(
                '.datafield_timetable-alerttimeinvalid,.datafield_timetable-alerttimeconflicts,.datafield_timetable-alertinputrequired'
            ).hide();

            let data = [];
            const $data = $timetable.find('.datafield_timetable-data');

            const $addBtn = $timetable.find('.datafield_timetable-add_btn');

            const conflicts = (from, to) => {
                for (let a = 0; a < data.length; a++) {
                    const activity = data[a];
                    if (from < activity.to && to > activity.from) {
                        return true;
                    }
                }

                return false;
            };

            const getMaxTime = () => data.filter(c => c.valid).reduce((prev, curr) => curr.to > prev ? curr.to : prev, 0);

            const formatcategories = categories => categories.map(cat => `${cat.id}=${cat.value}`).join(',');

            const updateData = () => {
                data = [];
                const $slots = $tbody.find('.datafield_timetable-slot');
                for (let s = 0; s < $slots.length; s++) {
                    const $slot = $($slots[s]);
                    const from = (parseInt($slot.find('.datafield_timetable-fromhourselect').val()) * 60) +
                        parseInt($slot.find('.datafield_timetable-fromminuteselect').val());
                    const to = (parseInt($slot.find('.datafield_timetable-tohourselect').val()) * 60) +
                        parseInt($slot.find('.datafield_timetable-tominuteselect').val());
                    const activity = $slot.find('.datafield_timetable-activityinput').val().trim();
                    const $categoryselects = $slot.find('.datafield_timetable-categoryselect');
                    const categories = [];
                    for (let c = 0; c < $categoryselects.length; c++) {
                        const $category = $($categoryselects[c]);
                        categories.push({
                            id: parseInt($category.attr('data-id')),
                            value: parseInt($category.val())
                        });
                    }

                    const timeinvalid = from >= to;
                    const timeconflicts = conflicts(from, to);
                    const inputrequired = !activity;

                    data.push({
                        from: from,
                        to: to,
                        activity: activity,
                        categories: categories,
                        valid: !timeinvalid && !timeconflicts && !inputrequired
                    });

                    if ((from || to) && timeinvalid) {
                        $slot.find('.datafield_timetable-alerttimeinvalid').show();
                    } else {
                        $slot.find('.datafield_timetable-alerttimeinvalid').hide();
                    }

                    if (timeconflicts) {
                        $slot.find('.datafield_timetable-alerttimeconflicts').show();
                    } else {
                        $slot.find('.datafield_timetable-alerttimeconflicts').hide();
                    }

                    if (inputrequired) {
                        $slot.find('.datafield_timetable-alertinputrequired').show();
                    } else {
                        $slot.find('.datafield_timetable-alertinputrequired').hide();
                    }
                }

                $data.val(data
                    .filter(d => d.valid)
                    .sort((d1, d2) => d1.from - d2.from)
                    .map(d => `${d.from};${d.to};${encodeURI(d.activity)};${formatcategories(d.categories)}`)
                    .join('\n'));

                console.log($data.val());
            };

            const addActivity = (from = 0, to = 0, activity = '', categories = [], autofocus = true) => {
                const $newSlot = $template.clone();
                const $deleteBtn = $newSlot.find('.datafield_timetable-delete_btn');
                const $fromHourSelect = $newSlot.find('.datafield_timetable-fromhourselect');
                const $fromMinuteSelect = $newSlot.find('.datafield_timetable-fromminuteselect');
                const $toHourSelect = $newSlot.find('.datafield_timetable-tohourselect');
                const $toMinuteSelect = $newSlot.find('.datafield_timetable-tominuteselect');
                const $activityInput = $newSlot.find('.datafield_timetable-activityinput');
                const $categoryselects = $newSlot.find('.datafield_timetable-categoryselect');

                let fromHour = 0;
                let fromMinute = 0;
                if (from) {
                    fromHour = Math.floor(from / 60) % 24;
                    fromMinute = from % 60;
                } else {
                    const maxTime = getMaxTime();
                    fromHour = Math.floor(maxTime / 60);
                    fromMinute = maxTime % 60;
                }

                $fromHourSelect.val(fromHour);
                $fromMinuteSelect.val(fromMinute);

                if (to) {
                    const toHour = Math.floor(to / 60) % 24;
                    const toMinute = to % 60;
                    $toHourSelect.val(toHour);
                    $toMinuteSelect.val(toMinute);
                }

                if (activity) {
                    $activityInput.val(activity);
                }

                if (categories && categories.length) {
                    for (let c = 0; c < $categoryselects; c++) {
                        const $category = $($categoryselects[c]);
                        const id = parseInt($category.attr('data-id'));
                        const categorydata = categories.filter(c => parseInt(c.id) === id);
                        if (categorydata.length) {
                            $category.val(categorydata[0].value);
                        }
                    }
                }

                $deleteBtn.click(() => {
                    $newSlot.remove();
                    updateData();
                });

                $newSlot.find('input,select').change(() => {
                    updateData();
                });

                $newSlot.removeClass('datafield_timetable-slot_template')
                    .addClass('datafield_timetable-slot')
                    .appendTo($tbody)
                    .show();

                updateData();

                if (autofocus) {
                    $fromHourSelect.focus();
                }
            };

            const parserawcategories = rawcategory => {
                const raw = rawcategory.split('=');
                if (raw === 2) {
                    return {
                        id: parseInt(raw[0]),
                        value: parseInt(raw[1])
                    };
                }

                return null;
            };

            const initialize = () => {
                const rows = $data.val().split('\n').map(d => d.split(';')).filter(d => d.length === 4);
                for (let r = 0; r < rows.length; r++) {
                    const row = rows[r];
                    addActivity(
                        parseInt(row[0]),
                        parseInt(row[1]),
                        decodeURI(row[2]),
                        row[3].split(',').map(c => parserawcategories(c)).filter(c => c !== null),
                        false
                    );
                }
            };

            $template.hide();

            $addBtn.click(() => {
                addActivity();
            });

            initialize();
        }
    });
});
