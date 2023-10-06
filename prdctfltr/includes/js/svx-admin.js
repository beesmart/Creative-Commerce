(function($) {
    "use strict";

    function u(e) {
        return typeof e == 'undefined' || e == 'false' ? false : e;
    }

    function uE(e) {
        return typeof e == 'undefined' || e == '' ? false : e;
    }

    function __check_for_undefined() {
        if (u(svx.extras.presets) === false) {
            svx.extras.presets = {};
        }
        if (u(svx.extras.presets.loaded_settings) === false) {
            svx.extras.presets.loaded_settings = {};
        }
        if (u(svx.extras.presets.set) === false) {
            svx.extras.presets.set = {};
        }


        if (u(svx.extras.options) === false) {
            svx.extras.options = {};
        }

        if (u(svx.extras.options.general) === false) {
            svx.extras.options.general = {};
        }

        if (u(svx.extras.options.install) === false) {
            svx.extras.options.install = {
                templates: {},
                actions: {},
                ajax: {},
            };
        }

        if (u(svx.extras.options.manager) === false) {
            svx.extras.options.manager = {};
        }
        if (u(svx.extras.options.presets) === false) {
            svx.extras.options.presets = {};
        }
    }

    function __fix_generals(t) {

        __check_for_undefined();

        $.each(svx.settings, function(i, b) {
            switch (b.section) {
                case 'manager':
                    switch (b.id) {
                        case 'supported_overrides':
                        case 'shop_preset':
                            if (___check_load(t)) {
                                b.val = u(svx.extras.options.general[b.id]) ? svx.extras.options.general[b.id] : b.default;
                            } else {
                                svx.extras.options.general[b.id] = u(b.val) ? b.val : b.default;
                            }
                            break;
                        default:
                            break;
                    }
                    break;
                case 'integration':
                    switch (b.id) {
                        case 'el_result_count':
                            if (___check_load(t)) {
                                b.val = u(svx.extras.options.install.templates.result_count) ? svx.extras.options.install.templates.result_count : b.default;
                            } else {
                                svx.extras.options.install.templates.result_count = u(b.val) ? b.val : b.default;
                            }
                            break;
                        case 'el_orderby':
                            if (___check_load(t)) {
                                b.val = u(svx.extras.options.install.templates.orderby) ? svx.extras.options.install.templates.orderby : b.default;
                            } else {
                                svx.extras.options.install.templates.orderby = u(b.val) ? b.val : b.default;
                            }
                            break;
                        case 'actions':
                            if (___check_load(t)) {
                                b.val = u(svx.extras.options.install[b.id]) ? svx.extras.options.install[b.id] : b.default;
                            } else {
                                svx.extras.options.install[b.id] = u(b.val) ? b.val : b.default;
                            }
                            break;
                        default:
                            if (___check_load(t)) {
                                b.val = u(svx.extras.options.general[b.id]) ? svx.extras.options.general[b.id] : b.default;
                            } else {
                                svx.extras.options.general[b.id] = u(b.val) ? b.val : b.default;
                            }
                            break;
                    }
                    break;
                case 'ajax':
                    if (___check_load(t)) {
                        b.val = u(svx.extras.options.general[b.id]) ? svx.extras.options.general[b.id] : false;
                        if (b.val === false) {
                            b.val = u(svx.extras.options.install[b.section]) && u(svx.extras.options.install[b.section][b.id]) ? svx.extras.options.install[b.section][b.id] : b.default;
                        }
                    } else {
                        if (u(svx.extras.options.general[b.id])) {
                            svx.extras.options.general[b.id] = u(b.val) ? b.val : b.default;
                        } else {
                            svx.extras.options.install[b.section][b.id] = u(b.val) ? b.val : b.default;
                        }
                    }
                    break;
                case 'general':
                    if (___check_load(t)) {
                        b.val = u(svx.extras.options.general[b.id]) ? svx.extras.options.general[b.id] : b.default;
                    } else {
                        svx.extras.options.general[b.id] = u(b.val) ? b.val : b.default;
                    }
                    break;
                case 'analytics':
                    if (___check_load(t)) {
                        b.val = u(svx.extras.options.general[b.id]) ? svx.extras.options.general[b.id] : b.default;
                    } else {
                        svx.extras.options.general[b.id] = u(b.val) ? b.val : b.default;
                    }
                    break;
                default:
                    break;
            }
        });

        if (u(svx.extras.options.general['supported_overrides'])) {
            if (u(svx.extras.options.manager) === false) {
                svx.extras.options.manager = {};
            }

            $.each(svx.extras.options.general['supported_overrides'], function(r, y) {
                var o = [];
                if (___check_load(t)) {
                    if (u(svx.settings['_pf_manager_' + y])) {
                        svx.settings['_pf_manager_' + y].val = u(svx.extras.options.manager[y]) ? svx.extras.options.manager[y] : [];
                    }
                } else {
                    if (u(svx.settings['_pf_manager_' + y])) {
                        if (u(svx.extras.options.manager[y]) === false) {
                            svx.extras.options.manager[y] = {};
                        }

                        svx.extras.options.manager[y] = svx.settings['_pf_manager_' + y].val;
                    }
                }

            });
        }

        if (___check_load(t) === false) {

            svx.solids = {};

            var j = {};
            $.each(svx.extras.options, function(w, x) {
                if (w == 'install' || w == 'general') {
                    j[w] = x;
                }
            });

            j.manager = svx.extras.options.manager;
            j.presets = [];

            $.each(svx.extras.presets.set, function(w, x) {
                j.presets.push({ slug: w, name: x });
            });

            svx.solids['_prdctfltr_autoload'] = {
                val: j,
                autoload: 'solid',
            };

            $(document).trigger('svx-filters-save', [svx.settings.filters]);
            svx.extras.presets.edited[svx.extras.presets.loaded] = svx.extras.presets.loaded_settings;

            $.each(svx.extras.presets.edited, function(z, x) {
                var k = '_prdctfltr_preset_' + z + (u(svx.language) === false ? '' : '_' + svx.language);
                svx.solids[k] = {
                    val: x,
                    autoload: 'solid',
                };
            });

        }

    }

    $(document).on('svx-load-product_filter', function(e, f) {
        if (u(svx.slug) == 'product_filter') {
            svx.extras.presets.edited = {};
            svx.extras.presets.manager = {};
            svx.extras.presets.deleted = [];
        }
        __fix_generals('load');
    });

    $(document).on('svx-save-product_filter', function(e, f) {
        __fix_generals('save');
    });

    $(document).on('svx-filters-save', function(e, f) {

        var r = [],
            t = 'save';

        r = u(svx.settings.filters.val) ? svx.settings.filters.val : [];
        var s = [];

        $.each(r, function(i, b) {
            if (u(b)) {

                s.push({});

                $.each(svx.settings.filters.settings[b.type], function(n, p) {
                    s[i][n] = u(svx.settings.filters.val[i]) && u(svx.settings.filters.val[i][n]) ? svx.settings.filters.val[i][n] : (u(b.type) && u(svx.settings.filters.settings[b.type].default) ? svx.settings.filters.settings[b.type].default : '');
                });

                s[i]['style'] = u(svx.settings.filters.val[i]['style']) ? svx.settings.filters.val[i]['style'] : '';
                if (s[i]['style'] == 'color' || s[i]['style'] == 'image') {
                    s[i]['size'] = u(svx.settings.filters.val[i]['size']) ? svx.settings.filters.val[i]['size'] : '';
                    s[i]['label'] = u(svx.settings.filters.val[i]['label']) ? svx.settings.filters.val[i]['label'] : '';
                    s[i]['swatchDesign'] = u(svx.settings.filters.val[i]['swatchDesign']) ? svx.settings.filters.val[i]['swatchDesign'] : '';
                }
                if (s[i]['style'] == 'text') {
                    s[i]['text'] = u(svx.settings.filters.val[i]['text']) ? svx.settings.filters.val[i]['text'] : '';
                }
                if (u(svx.settings.filters.val[i]['options'])) {
                    s[i]['options'] = svx.settings.filters.val[i]['options'];
                }

                if (u(b.filter) === false) {
                    b.filter = b.type;
                }

                if (u(b.custom_order)) {
                    s[i].custom_order = true;
                }

                if (b.filter == 'taxonomy') {
                    __check_taxonomy(s[i], t);
                }
                if (b.filter == 'range') {
                    __check_range(s[i], t);
                }
                if (b.filter == 'price_range') {
                    __check_price_range(s[i], t);
                }
                if (b.filter == 'meta') {
                    __check_meta(s[i], t);
                }
                if (b.filter == 'meta_range') {
                    __check_meta_range(s[i], t);
                }
                if (b.filter == 'vendor') {
                    __check_vendor(s[i], t);
                }
                if (b.filter == 'orderby') {
                    __check_orderby(s[i], t);
                }
                if (b.filter == 'search') {
                    __check_search(s[i], t);
                }
                if (b.filter == 'instock') {
                    __check_instock(s[i], t);
                }
                if (b.filter == 'price') {
                    __check_price(s[i], t);
                }
                if (b.filter == 'per_page') {
                    __check_per_page(s[i], t);
                }

                if ($.inArray(b.filter, ['search', 'price_range']) == -1) {
                    __check_style(s[i], t);
                }

                if (b.filter !== 'price_range') {
                    s[i].filter = b.type;
                }

            }

        });

        svx.extras.presets.loaded_settings.filters = u(s) ? s : [];

        _make_more_filter_options('general', t);
        _make_more_filter_options('style', t);
        _make_more_filter_options('adoptive', t);
        _make_more_filter_options('responsive', t);

    });

    $(document).on('svx-filters-load', function(e, f) {

        var r = [],
            t = 'load';

        r = u(svx.extras.presets.loaded_settings.filters) ? svx.extras.presets.loaded_settings.filters : [];

        $.each(r, function(i, b) {
            if (b.filter == 'taxonomy') {
                __check_taxonomy(b, t);
            }
            if (b.filter == 'range') {
                if (b.taxonomy == 'price') {
                    __check_price_range(b, t);
                } else {
                    __check_range(b, t);
                }
            }
            if (b.filter == 'meta') {
                __check_meta(b, t);
            }
            if (b.filter == 'meta_range') {
                __check_meta_range(b, t);
            }
            if (b.filter == 'vendor') {
                __check_vendor(b, t);
            }
            if (b.filter == 'orderby') {
                __check_orderby(b, t);
            }
            if (b.filter == 'search') {
                __check_search(b, t);
            }
            if (b.filter == 'instock') {
                __check_instock(b, t);
            }
            if (b.filter == 'price') {
                __check_price(b, t);
            }
            if (b.filter == 'per_page') {
                __check_per_page(b, t);
            }

            if ($.inArray(b.filter, ['search', 'price_range']) == -1) {
                __check_style(b, t);
            }

            if (u(b.filter) === false) {
                b.filter = b.type;
            } else {
                b.type = b.filter;
            }

        });

        f.val = r;

        _make_more_filter_options('general', t);
        _make_more_filter_options('style', t);
        _make_more_filter_options('adoptive', t);
        _make_more_filter_options('responsive', t);

    });

    function _make_more_filter_options(e, t) {
        var a = e.substr(0, 1) + '_';

        if (___check_load(t)) {
            $.each(svx.settings, function(i, o) {
                if (o.id.substr(0, 2) == a) {
                    var n = o.id.substr(2);
                    svx.settings[i].val = u(svx.extras.presets.loaded_settings[e]) && u(svx.extras.presets.loaded_settings[e][n]) ? u(svx.extras.presets.loaded_settings[e][n]) : false;
                    if (svx.settings[i].val === false) {
                        svx.settings[i].val = svx.settings[i].default;
                    }
                }
            });
        } else {
            $.each(svx.settings, function(i, o) {
                if (o.id.substr(0, 2) == a) {

                    var n = o.id.substr(2);
                    if (u(svx.extras.presets.loaded_settings[e]) === false) {
                        svx.extras.presets.loaded_settings[e] = {};
                    }
                    svx.extras.presets.loaded_settings[e][n] = u(svx.settings[i].val) ? svx.settings[i].val : false;
                    if (svx.settings[i].val === false) {
                        svx.extras.presets.loaded_settings[e][n] = svx.settings[i].default;
                    }
                    if (svx.extras.presets.loaded_settings[e][n] === false) {
                        svx.extras.presets.loaded_settings[e][n] = svx.settings[i].default;
                    }

                }
            });
        }
    }

    function __check_style(b, t) {
        if (___check_load(t)) {
            if (u(b.style)) {

                b.options = [];

                if (u(b.style.terms)) {
                    if (u(b.style.terms[0])) {
                        $.each(b.style.terms, function(i, c) {
                            b.options.push({
                                'id': u(c.id) ? c.id : '',
                                'slug': u(c.id) ? c.id : '',
                                'name': u(c.title) ? c.title : '',
                                'value': u(c.data) ? c.data : '',
                                'data': u(c.value) ? c.value : '',
                                'tooltip': u(c.tooltip) ? c.tooltip : '',
                            });
                        });
                    }
                }

                if (u(b.style.style)) {
                    if (u(b.style.style.type) == 'text') {
                        b.text = {
                            active: uE(b.style.style.active) ? b.style.style.active : '#1e73be',
                            disabled: uE(b.style.style.disabled) ? b.style.style.disabled : '#dddddd',
                            normal: uE(b.style.style.normal) ? b.style.style.normal : '#bbbbbb',
                            outofstock: uE(b.style.style.outofstock) ? b.style.style.outofstock : '#e45050',
                            style: uE(b.style.style.css) ? b.style.style.css : 'border',
                        };
                    }

                    if ($.inArray(u(b.style.style.type), ['color', 'image']) !== -1) {
                        b.size = u(b.style.size) ? b.style.size : '';
                        b.label = u(b.style.label) ? b.style.label : '';
                        b.swatchDesign = u(b.style.swatchDesign) ? b.style.swatchDesign : '';
                    }

                    b.style = u(b.style.style.type) ? (b.style.style.type == 'select' ? 'selectbox' : b.style.style.type) : '';
                } else {
                    b.style = '';
                }

            }
        } else {
            if (u(b.style) || u(b.options)) {

                var o = {
                    style: {},
                };

                if (u(b.options)) {
                    o.terms = [];
                    $.each(b.options, function(i, c) {
                        o.terms.push({
                            'id': u(c.id) ? c.id : '',
                            'slug': u(c.id) ? c.id : '',
                            'title': u(c.name) ? c.name : '',
                            'value': u(c.data) ? c.data : '',
                            'data': u(c.value) ? c.value : '',
                            'tooltip': u(c.tooltip) ? c.tooltip : '',
                        });
                    });
                    delete b.options;
                }

                o.style.type = u(b.style) ? (b.style == 'selectbox' ? 'select' : b.style) : '';

                if (u(o.style.type) == 'text') {
                    o.style.active = u(b.text) && uE(b.text.active) ? b.text.active : '#1e73be';
                    o.style.disabled = u(b.text) && uE(b.text.disabled) ? b.text.disabled : '#dddddd';
                    o.style.normal = u(b.text) && uE(b.text.normal) ? b.text.normal : '#bbbbbb';
                    o.style.outofstock = u(b.text) && uE(b.text.outofstock) ? b.text.outofstock : '#e45050';
                    o.style.css = u(b.text) && uE(b.text.style) ? b.text.style : 'border';
                    delete b.text;
                }

                if ($.inArray(u(o.style.type), ['color', 'image']) !== -1) {
                    o.size = b.size;
                    o.label = b.label;
                    o.swatchDesign = b.swatchDesign;
                }

                if ($.inArray(u(o.style.type), ['', 'text', 'color', 'image', 'select', 'html', 'system', 'selectize']) !== -1) {
                    b.style = o;
                }

            } else {
                delete b.style;
            }
        }
    }

    function ___check_load(t) {
        switch (t) {
            case 'load':
                return true;
            default:
                return false;
        }
    }

    function ___check_title(b, t) {
        if (___check_load(t)) {
            b.name = u(b.title) ? b.title : '';
            delete b.title;
        } else {
            b.title = u(b.name) ? b.name : '';
            delete b.name;
        }
    }

    function __check_taxonomy(b, t) {
        ___check_title(b, t);
    }

    function __check_range(b, t) {
        ___check_title(b, t);
    }

    function __check_meta(b, t) {
        ___check_title(b, t);
        if (___check_load(t)) {
            b.meta_key = u(b.key) ? b.key : '';
            b.meta_compare = u(b.compare) ? b.compare : '';
            b.meta_type = u(b.type) ? b.type : '';
            b.taxonomy = 'meta';

            delete b.key;
            delete b.compare;
            delete b.type;
        } else {
            b.key = u(b.meta_key) ? b.meta_key : '';
            b.compare = u(b.meta_compare) ? b.meta_compare : '';
            b.type = u(b.meta_type) ? b.meta_type : '';

            delete b.meta_key;
            delete b.meta_compare;
            delete b.meta_type;
        }
    }

    function __check_meta_range(b, t) {
        ___check_title(b, t);
        if (___check_load(t)) {
            b.meta_key = u(b.key) ? b.key : '';
            b.meta_numeric = u(b.numeric) ? b.numeric : '';
            b.taxonomy = 'meta';

            delete b.key;
            delete b.numeric;
        } else {
            b.key = u(b.meta_key) ? b.meta_key : '';
            b.numeric = u(b.meta_numeric) ? b.meta_numeric : '';

            delete b.meta_key;
            delete b.meta_numeric;
        }
    }

    function __check_vendor(b, t) {
        ___check_title(b, t);
        if (___check_load(t)) {
            b.taxonomy = 'meta';
        }
    }

    function __check_orderby(b, t) {
        ___check_title(b, t);
        if (___check_load(t)) {
            b.taxonomy = 'meta';
        }
    }

    function __check_search(b, t) {
        ___check_title(b, t);
        if (___check_load(t)) {
            b.taxonomy = 'meta';
        }
    }

    function __check_instock(b, t) {
        ___check_title(b, t);
        if (___check_load(t)) {
            b.taxonomy = 'meta';
        }
    }

    function __check_price(b, t) {
        ___check_title(b, t);
        if (___check_load(t)) {
            b.taxonomy = 'meta';
        }
    }

    function __check_price_range(b, t) {
        ___check_title(b, t);
        if (___check_load(t)) {
            b.filter = 'price_range';
            b.type = 'price_range';
        } else {
            b.taxonomy = 'price';
            b.filter = 'range';
            b.type = 'range';
        }
    }

    function __check_per_page(b, t) {
        ___check_title(b, t);
        if (___check_load(t)) {
            b.taxonomy = 'meta';
        }
    }

    $(document).on('svx-send-ajax-settings', function(e, f) {
        if (svx.slug == 'product_filter') {
            if (u(svx.extras.presets.deleted) && svx.extras.presets.deleted.length > 0) {
                f.delete = [];
                $.each(svx.extras.presets.deleted, function(i, o) {
                    f.delete.push('_prdctfltr_preset_' + o);
                });
            }
        }
    });


    function sanitize_title(s) {
        if (u(s)) {
            s = s.toString().replace(/^\s+|\s+$/g, '');
            s = s.toLowerCase();

            var from = "ąàáäâèéëêęìíïîłòóöôùúüûñńçěšśčřžźżýúůďťňćđ·/_,:;#";
            var to = "aaaaaeeeeeiiiiloooouuuunncesscrzzzyuudtncd-------";

            for (var i = 0, l = from.length; i < l; i++) {
                s = s.replace(new RegExp(from.charAt(i), 'g'), to.charAt(i));
            }

            s = s.replace('.', '-')
                .replace(/[^a-z0-9 -]/g, '')
                .replace(/\s+/g, '-')
                .replace(/-+/g, '-');
        } else {
            s = '';
        }

        return s;
    }

    function prdctfltr_charts() {

        $('.pf-analytics-chart').each(function() {

            var chartData = $.parseJSON($(this).attr('data-chart'));

            var chartArray = [];
            for (var key in chartData) {
                if (chartData.hasOwnProperty(key)) {
                    chartArray.push([key, chartData[key]]);
                }
            };

            var data = new google.visualization.DataTable();
            data.addColumn('string', 'Term');
            data.addColumn('number', 'Count');
            data.addRows(chartArray);

            var options = {
                hAxis: {
                    titleTextStyle: { color: '#607d8b' },
                    gridlines: { count: 0 },
                    textStyle: { color: '#b0bec5', fontName: 'Roboto', fontSize: '12', bold: true },
                },
                vAxis: {
                    minValue: 0,
                    gridlines: { color: '#37474f', count: 4 },
                    baselineColor: 'transparent'
                },
                legend: { position: 'right', alignment: 'left', textStyle: { color: '#607d8b', fontName: 'Roboto', fontSize: '12' } },
                colors: ["#6699ff", "#00bcff", "#03a9f4", "#00d8f7", "#00ebc4", "#92f78f", "#f9f871", "#8385e5", "#9272c9", "#9960ac", "#994f8f", "#934173", "#92f78f", "#f9f871", "#002272", "#003c93", "#0059b6", "#3a78da", "#8385e5"],
                areaOpacity: 0.24,
                lineWidth: 1,
                backgroundColor: 'transparent',
                chartArea: {
                    backgroundColor: "transparent",
                    width: '95%',
                    height: '95%',
                },
                height: 360,
                width: 500,
                pieSliceBorderColor: 'transparent',
                pieSliceTextStyle: { color: '#fff' },
                pieHole: 0.65,
                bar: { groupWidth: "40" },
                colorAxis: { colors: ["#3f51b5", "#2196f3", "#03a9f4", "#00bcd4"] },
                backgroundColor: 'transparent',
                datalessRegionColor: '#37474f',
                displayMode: 'regions',
            };

            var chart = new google.visualization.PieChart(document.getElementById($(this).attr('id')));
            chart.draw(data, options);

        });

    }

    google.load('visualization', '1.0', { 'packages': ['corechart'] });

    $(document).on('svx-fields-on-screen-product_filter', function(e, f) {
        if ($('.pf-analytics-chart').length > 0) {
            prdctfltr_charts();
        }
    });

    $(document).on('click', '#pf-analytics-reset', function(e, f) {

        var data = {
            action: 'prdctfltr_analytics_reset'
        };

        $.post(svx.ajax, data, function(response) {
            window.location.href = window.location.href;
        });

    });

})(jQuery);