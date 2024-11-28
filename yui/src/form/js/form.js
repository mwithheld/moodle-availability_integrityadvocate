/**
 * JavaScript for form editing completion conditions.
 *
 * @module moodle-availability_integrityadvocate-form
 */
M.availability_integrityadvocate = M.availability_integrityadvocate || {};

/**
 * @class M.availability_integrityadvocate.form
 * @extends M.core_availability.plugin
 */
M.availability_integrityadvocate.form = Y.Object(M.core_availability.plugin);

/**
 * Initialises this plugin. 1+ params are passed in from PHP.
 *
 * @method initInner
 * @param {Array} cms Array of objects containing cmid => name
 */
M.availability_integrityadvocate.form.initInner = function(cms) {
    this.cms = cms;
};

/**
 * This function does the main work. It gets called after the user
 * chooses to add an availability restriction of this type. You have
 * to return a YUI node representing the HTML for the plugin controls.
 *
 * @param {string} json
 * @return {Y.Node} YUI node representing the HTML for the plugin controls
 */
M.availability_integrityadvocate.form.getNode = function(json) {
    var debug = true;
    debug && window.console.log('M.availability_integrityadvocate.form.getNode' + '::Started with json=', json);

    if (this.cms === undefined || this.cms.constructor !== Array) {
        this.cms = [];
    }

    // Create HTML structure.
    var html = '<span class="col-form-label p-r-1"> ' + M.util.get_string('title', 'availability_integrityadvocate') + '</span>' +
        ' <span class="availability-group form-group"><label>' +
        '<span class="accesshide">' + M.util.get_string('label_cm', 'availability_integrityadvocate') + ' </span>' +
        '<select class="custom-select" name="cm" title="' + M.util.get_string('label_cm', 'availability_integrityadvocate') + '">' +
        '<option value="0">' + M.util.get_string('choosedots', 'moodle') + '</option>';
    var cm;
    for (var i = 0; i < this.cms.length; i++) {
        cm = this.cms[i];
        // String has already been escaped using format_string.
        html += '<option value="' + cm.id + '">' + cm.name + '</option>';
    }
    html += '</select></label> <label><span class="accesshide">' +
        M.util.get_string('label_completion', 'availability_integrityadvocate') +
        ' </span><select class="custom-select" ' +
        'name="e" title="' + M.util.get_string('label_completion', 'availability_integrityadvocate') + '">' +
        '<option value="1">' + M.util.get_string('option_valid', 'availability_integrityadvocate') + '</option>' +
        '<option value="0">' + M.util.get_string('option_invalid', 'availability_integrityadvocate') + '</option>' +
        '</select></label></span>';
    var node = Y.Node.create('<span class="form-inline">' + html + '</span>');

    // Set initial values.
    if (json.cm !== undefined &&
        node.one('select[name=cm] > option[value=' + json.cm + ']')) {
        node.one('select[name=cm]').set('value', '' + json.cm);
    }
    if (json.e !== undefined) {
        node.one('select[name=e]').set('value', '' + json.e);
    }

    // Add event handlers (first time only).
    if (!M.availability_integrityadvocate.form.addedEvents) {
        M.availability_integrityadvocate.form.addedEvents = true;
        var root = Y.one('.availability-field');
        root.delegate('change', function() {
            // The key point is this update call. This call will update
            // the JSON data in the hidden field in the form, so that it
            // includes the new value of the checkbox.
            // I.
            // Whichever dropdown changed, just update the form.
            M.core_availability.form.update();
        }, '.availability_integrityadvocate select');
    }

    return node;
};

/**
 * This function gets passed the node (from above) and a value
 * object. Within that object, it must set up the correct values
 * to use within the JSON data in the form. Should be compatible
 * with the structure used in the __construct and save functions
 * within condition.php.
 *
 * @param {Object} value Value object (to be written to)
 * @param {Y.Node} node YUI node (same one returned from getNode)
 */
M.availability_integrityadvocate.form.fillValue = function(value, node) {
    var debug = true;
    debug && window.console.log('M.availability_integrityadvocate.form.fillValue' + '::Started with value=', value);
    debug && window.console.log('M.availability_integrityadvocate.form.fillValue' + '::Started with node=', node);

    value.cm = parseInt(node.one('select[name=cm]').get('value'), 10);
    value.e = parseInt(node.one('select[name=e]').get('value'), 10);

    debug && window.console.log('M.availability_integrityadvocate.form.fillValue' + '::Finished with cm=' + value.cm + '; e=' + value.e);
};

/**
 * If the user has selected something invalid, this optional
 * function can be included to report an error in the form. The
 * error will show immediately as a 'Please set' tag, and if the
 * user saves the form with an error still in place, they'll see
 * the actual error text.
 *
 * @param {Array} errors Array of errors (push new errors here)
 * @param {Y.Node} node YUI node (same one returned from getNode)
 */
M.availability_integrityadvocate.form.fillErrors = function(errors, node) {
    var debug = true;
    debug && window.console.log('M.availability_integrityadvocate.form.fillErrors' + '::Started with errors=', errors);

    var cmid = parseInt(node.one('select[name=cm]').get('value'), 10);
    if (cmid === 0) {
        debug && window.console.log('M.availability_integrityadvocate.form.fillValue' + '::Missing cmid');
        errors.push('availability_integrityadvocate:error_selectcmid');
    }

    var e = parseInt(node.one('select[name=e]').get('value'), 10);
    debug && window.console.log('M.availability_integrityadvocate.form.fillValue' + '::Got value for e=', e);
    debug && window.console.log('M.availability_integrityadvocate.form.fillErrors' + '::Finished');
};
