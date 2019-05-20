require(['jquery'], $ => {
    $(document).ready(() => {
        const $categorytemplate = $('.datafield_timetable-categorytemplate');
        const $addcategorybtn = $('#datafield_timetable-addcategorybtn');
        const $data = $('#datafield_timetable-categories_data');
        const $categoryinsertarea = $('#datafield_timetable-categoryinsertarea');

        $categorytemplate.hide();

        let data = [];

        const updatedata = () => {
            data = [];
            const $categories = $('.datafield_timetable-category');
            for (let c = 0; c < $categories.length; c++) {
                const $category = $($categories[c]);
                const $categorynameinput = $category.find('.datafield_timetable-categorynameinput');
                const category = {
                    id: parseInt($category.attr('data-id')),
                    name: $categorynameinput.val(),
                    items: []
                };
                const $categoryitems = $category.find('.datafield_timetable-categoryitem');
                for (let i = 0; i < $categoryitems.length; i++) {
                    const $input = $($categoryitems[i]).find('.datafield_timetable-itemnameinput');
                    category.items.push({
                        id: parseInt($input.attr('data-id')),
                        name: $input.val()
                    })
                }
                data.push(category);
            }

            $data.val(
                data.filter(c => c.name.trim()).map(c =>
                    `${c.id}=>${encodeURI(c.name)}\n` +
                    c.items.filter(ci => ci.name.trim()).map(ci => `${c.id},${ci.id}=>${encodeURI(ci.name)}`).join('\n')
                ).join('\n')
            );
        };

        // const initialize = () => {
        //     const defaultData = $data.val().split('\n').map(cat => cat.split('=>')).filter(cat => cat.length === 2);
        //     for (let d = 0; d < defaultData.length; d++) {
        //         const cat = defaultData[d];
        //         addCategory(decodeURI(cat[1]), cat[0], false);
        //     }
        // };
        //

        const initialize = () => {
            const defaultdata = [];
            const rawdata = $data.val();
            const rawlines = rawdata.split('\n');
            for (let r = 0; r < rawlines.length; r++) {
                const rawlinesplited = rawlines[r].split('=>');
                if (rawlinesplited.length !== 2) {
                    continue;
                }

                const rawid = rawlinesplited[0].split(',');
                if (rawid.length === 1) {
                    defaultdata.push({
                        id: rawid[0],
                        name: decodeURI(rawlinesplited[1]),
                        items: []
                    });
                } else if (rawid.length === 2) {
                    const category = defaultdata.filter(c => parseInt(c.id) === parseInt(rawid[0]));
                    if (category.length) {
                        category[0].items.push({
                            id: rawid[1],
                            name: decodeURI(rawlinesplited[1])
                        });
                    }
                }
            }

            for (let c = 0; c < defaultdata.length; c++) {
                addcategory(defaultdata[c].name, defaultdata[c].id, false, defaultdata[c].items);
            }
        };

        const getnextcategoryid = () => data.reduce((prev, category) => category.id > prev ? category.id : prev, 0) + 1;

        const addcategory = (name = '', id = 0, autofocus = true, items = []) => {
            const $newcategory = $categorytemplate.clone();

            id = id ? id : getnextcategoryid();
            $newcategory.attr('data-id', id);

            const $categoryitems = $newcategory.find('.datafield_timetable-categoryitems');
            const $itemtemplate = $categoryitems.find('.datafield_timetable-categoryitemtemplate');
            const $additembtn = $newcategory.find('.datafield_timetable-additembtn');
            const $categorynameinput = $newcategory.find('.datafield_timetable-categorynameinput');
            const $deletecategorybtn = $newcategory.find('.datafield_timetable-deletecategorybtn');

            $itemtemplate.hide();
            $categorynameinput.val(name);

            const getnextitemid = () => data.filter(c => parseInt(c.id) === parseInt(id))[0].items.reduce(
                (prev, item) => item.id > prev ? item.id : prev, 0
            ) + 1;

            const additem = (name = '', id = 0, autofocus = true) => {
                id = id ? id : getnextitemid();

                const $newitem = $itemtemplate.clone();
                const $input = $newitem.find('.datafield_timetable-itemnameinput');
                $input.val(name);
                $input.attr('data-id', id);
                $input.change(() => {
                    updatedata();
                });

                const $deleteBtn = $newitem.find('.datafield_timetable-deletebtn');
                $deleteBtn.click(() => {
                    $newitem.remove();
                    updatedata();
                });

                $newitem.removeClass('datafield_timetable-categoryitemtemplate')
                    .addClass('datafield_timetable-categoryitem')
                    .show();

                $categoryitems.append($newitem);
                if (autofocus) {
                    $input.focus();
                }

                updatedata();
            };

            $categorynameinput.change(() => {
                updatedata();
            });

            $deletecategorybtn.click(() => {
                $newcategory.remove();
                updatedata();
            });

            $additembtn.click(() => {
                additem();
            });

            $newcategory.insertBefore($categoryinsertarea)
                .removeClass('datafield_timetable-categorytemplate')
                .addClass('datafield_timetable-category')
                .show();

            if (autofocus) {
                $categorynameinput.focus();
            }

            if (items && items.length) {
                for (let i = 0; i < items.length; i++) {
                    additem(items[i].name, items[i].id, autofocus);
                }
            }
        };

        $addcategorybtn.click(() => {
            addcategory();
        });

        initialize();
    });
});
