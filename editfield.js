require(['jquery'], $ => {
    $(document).ready(() => {
        const $categories = $('#datafield_timetable-categories');
        const $template = $('.datafield_timetable-category_template');
        const $data = $('#datafield_timetable-categories_data');
        const $addBtn = $('#datafield_timetable-add_btn');

        $template.hide();

        let data = [];

        const getNextId = () => {
            return data.reduce((prev, curr) => curr.id > prev ? curr.id : prev, 0) + 1;
        };

        const updateData = () => {
            data = [];
            const categoriesCollection = $categories.find('.datafield_timetable-category');
            if (categoriesCollection && categoriesCollection.length) {
                for (let c = 0; c < categoriesCollection.length; c++) {
                    const $input = $(categoriesCollection[c]).find('.datafield_timetable-category_input');
                    data.push({
                        id: parseInt($input.attr('data-id')),
                        name: $input.val()
                    });
                }
            }

            $data.val(data.filter(d => d.name.trim()).map(d => `${d.id}=>${encodeURI(d.name)}`).join('\n'));
        };

        const addCategory = (name = '', id = 0, autofocus = true) => {
            if (!id) {
                id = getNextId();
            }

            const $newCategory = $template.clone();
            const $input = $newCategory.find('.datafield_timetable-category_input');
            $input.val(name);
            $input.attr('data-id', id);
            $input.change(() => {
               updateData();
            });

            const $deleteBtn = $newCategory.find('.datafield_timetable-delete_btn');
            $deleteBtn.click(() => {
                $newCategory.remove();
                updateData();
            });

            $newCategory.removeClass('datafield_timetable-category_template')
                .addClass('datafield_timetable-category')
                .show();

            $categories.append($newCategory);
            if (autofocus) {
                $input.focus();
            }

            updateData();
        };

        const initialize = () => {
            const defaultData = $data.val().split('\n').map(cat => cat.split('=>')).filter(cat => cat.length === 2);
            for (let d = 0; d < defaultData.length; d++) {
                const cat = defaultData[d];
                addCategory(decodeURI(cat[1]), cat[0], false);
            }
        };

        $addBtn.click(() => {
           addCategory();
        });

        initialize();
    });
});
