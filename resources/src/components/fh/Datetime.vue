<template>
    <div>
        <div class="form-group">
            <div class="input-group input-group-sm">
                <div class="input-group-addon">
                    <i class="fa fa-calendar"></i>
                </div>
                <input type="text" autocomplete="off" class="form-control" />
            </div>
        </div>
    </div>
</template>

<script>
import 'daterangepicker/daterangepicker.css'
import * as $ from 'jquery'
import daterangepicker from 'daterangepicker'
import moment from 'moment'
import { MAGIC_VALUE_WRAPPER } from '@/utils/constants.js'

export default {

    props: {
        field: {
            type: String,
            required: true
        },
        guid: {
            type: String,
            required: true
        },
        value: {
            type: String,
            default: ''
        }
    },

    data: function () {
        return {
            val: this.value
        }
    },

    watch: {
        val () {
            this.$emit('input-value-updated', this.field, this.guid, this.val)
        }
    },

    mounted: function () {
        const self = this
        const $input = $(this.$el).find('input')
        const options = {
            autoUpdateInput: false,
            locale: {
                cancelLabel: 'Clear',
                firstDay: 1,
                format: 'YYYY-MM-DD HH:mm'
            },
            maxYear: 2050,
            minYear: 1900,
            ranges: {
                'Today': [moment(), moment()],
                'Yesterday': [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
                'Tomorrow': [moment().add(1, 'days'), moment().add(1, 'days')]
            },
            showDropdowns: true,
            singleDatePicker: true,
            timePicker: true,
            timePicker24Hour: true,
            timePickerIncrement: 5
        }

        if (this.val) {
            $input.val(this.val)
        }

        Object.keys(options.ranges).forEach(function (item) {
            // convert magic value to label, for example "%%today%%" becomes "Today"
            if (self.val === MAGIC_VALUE_WRAPPER + item.toLowerCase() + MAGIC_VALUE_WRAPPER) {
                $input.val(item)
                options.startDate = options.ranges[item][0].format(options.locale.format)
            }
        })

        $input.daterangepicker(options)

        // force display of custom ranges
        // @link https://github.com/dangrossman/daterangepicker/blob/v2.1.27/daterangepicker.js#L385
        $($($input).data('daterangepicker').container).find('.ranges ul').show()

        $input.on('apply.daterangepicker', function (e, picker) {
            $(this).val('Custom Range' === picker.chosenLabel ?
                picker.startDate.format(picker.locale.format) :
                picker.chosenLabel
            )
            self.val = 'Custom Range' === picker.chosenLabel ?
                picker.startDate.format(picker.locale.format) :
                MAGIC_VALUE_WRAPPER + picker.chosenLabel.toLowerCase() + MAGIC_VALUE_WRAPPER
        })

        $input.on('cancel.daterangepicker', function (e, picker) {
            $(this).val('')
            self.val = ''
        })
    }

}
</script>