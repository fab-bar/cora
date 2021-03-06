/*
 * Copyright (C) 2015 Marcel Bollmann <bollmann@linguistics.rub.de>
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy of
 * this software and associated documentation files (the "Software"), to deal in
 * the Software without restriction, including without limitation the rights to
 * use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of
 * the Software, and to permit persons to whom the Software is furnished to do so,
 * subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS
 * FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR
 * COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER
 * IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN
 * CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
 */

/* Class: Tagset

   Basic class that represents a tagset.  Should typically be extended by more
   specialized classes.

   The following functions could potentially be re-defined by more specialized
   classes:

     - processTags() is called when pre-fetching tagsets (server request
           "fetchTagsetsForFile").  This is typically only done for closed-
           class tagsets that are not 'lemma_sugg'.  POS tagsets can use this
           function to preprocess the tag list, splitting up composite tags,
           pre-generating HTML elements, etc.

     - buildTemplate() can be used to make changes to the line template.
           Basic input elements do not need this, and can be defined directly
           in the edit.php template, but if the input element requires
           information only available at runtime, it can be modified here.
           POS tagsets use this to insert pre-generated <select> elements.

     - handleEvent() extracts the new annotation value from an element,
           which should be the element the event in this.eventString relays to.

     - fill() fills this tagset's input elements according to the given data;
           this is called during page rendering.

     - update() is called on each tagset whenever the event defined by
           this.eventString happens.  Here, tagsets can react to a change
           being made by the user.  The tagset whose annotation is changed is
           also responsible for storing the new value in the provided data object.

   CAVEAT: Right now, update() is not guaranteed to be called for
           every annotation change, but only for changes triggered by the
           user.  If a tagset's update() routine makes further changes to
           the data, there are no further corresponding update() calls.

           As a consequence, tagsets cannot react to changes that have been
           made by other tagset classes as part of their update() call.
           This is not required ATM, but for clarity's sake, this process
           should probably be refactored to make **every** change go via
           update().
 */
var Tagset = new Class({
    id: null,
    shortname: null,
    longname: null,
    set_type: null,
    class: null,
    classname: 'Tagset',  /**< display name for this tagset class */
    searchable: true,  /**< can user search for annotations in this tagset? */
    exportable: true,  /**< can user export this tagset in CSV format? */
    split_class: false,  /**< is this tagset displayed as two? */
    value_key: '',  /**< defaults to "anno_" + class */
    settings: null,  /**< tagset-specific settings as defined in the database */

    tags: [],
    processed: false,

    showInputErrors: false,
    inputErrorClass: 'input_error',
    eventString: '',

    /* Constructor: Tagset

       Instantiate a new Tagset.

       Parameters:
         data - A data object containing tagset information.
     */
    initialize: function(data) {
        if(typeof(data.id) !== "undefined")
            this.id = data.id;
        if(typeof(data.shortname) !== "undefined")
            this.shortname = data.shortname;
        if(typeof(data.longname) !== "undefined")
            this.longname = data.longname;
        if(typeof(data.set_type) !== "undefined")
            this.set_type = data.set_type;
        if(typeof(data.class) !== "undefined") {
            this.class = data.class;
            this.value_key = "anno_" + this.class;
        }
        if(typeof(data.settings) !== "undefined" && data.settings) {
            this.settings = JSON.parse(data.settings);
        }
    },

    /* Function: setShowInputErrors

       Sets whether input errors should be shown for this tagset.
     */
    setShowInputErrors: function(value) {
        this.showInputErrors = value;
    },

    /* Function: isSplitClass

       Check whether this tagset class requires splitting up the tag values
       (e.g., for POS+morph).
     */
    isSplitClass: function() {
        return this.split_class;
    },

    /* Function: needsProcessing

       Check whether this tagset class needs a call to processTags() with the
       full taglist in order to work correctly.

       Returns false if the tagset class doesn't need processing, or if
       processing was already performed for this tagset instance.
     */
    needsProcessing: function() {
        if(this.set_type == "open")
            return false;
        return !this.processed;
    },

    /* Function: processTags

       Defines a list of tags for this tagset and preprocesses it, e.g.,
       generating HTML elements for the tags, splitting them up, etc. -- the
       implementation of this method is up to the individual subclasses.
     */
    processTags: function(tags) {
        this.tags = Array.map(
            Array.filter(tags,
                         function(e){return (e.needs_revision == 0);}),
            function(e) {return e.value;}
        );
        this.tags.sort(function (a, b) {
            return a.toLowerCase().localeCompare(b.toLowerCase());
        });
        this.processed = true;
    },

    /* Function: getValues

       Gets the tagset-specific annotation values from a token data object.

       Parameters:
         data - An object possibly containing annotations ({anno_pos: ...} etc.)
     */
    getValues: function(data) {
        var result = {};
        result[this.value_key] = data[this.value_key];
        return result;
    },

    /* Function: getValue

       Get the main annotation value from a token data object.
     */
    getValue: function(data) {
        return data[this.value_key];
    },

    /* Function: generateOptgroupFor

       Generates and returns an <optgroup> element containing all tags supplied
       to this method.

       Parameters:
         tags - List of tags the <optgroup> should contain
         label - Label for the <optgroup>, defaults to "Alle Tags"
         cls - CSS class for the <optgroup> and all contained elements
     */
    generateOptgroupFor: function(tags, label, cls) {
        if(tags.length < 1)
            return null;
        label = label || _("EditorTab.general.allTags");
        var optgroup = new Element('optgroup', {label: label, class: cls});
        var opt;
        Array.each(tags, function(tag) {
            if(typeof(tag) === "string")
                opt = {text: tag, value: tag, class: cls};
            else
                opt = {text: tag.text, value: tag.value, class: cls};
            optgroup.grab(new Element('option', opt));
        });
        return optgroup;
    },

    /* Function: buildTemplate

       Update an editor line template for this tagset.

       Parameters:
         td - Table cell element to update
     */
    buildTemplate: function(td) {
        return;
    },

    /* Function: getEventData

       Return event types and event handlers for this tagset.

       This function only needs to be re-implemented by individual tagsets if
       more than one event type & handler is required.

       Returns:
         An array of objects with the following properties:
           type - An event type that should be registered by a DataTable
                  containing this element.
           handler - A function that handles this event for this tagset.
     */
    getEventData: function() {
        return [{type: this.eventString, handler: this.handleEvent.bind(this)}];
    },

    /* Function: handleEvent

       Event handler that is returned by getEventData() by default.

       This default event handler is set up to work with <input> elements, and
       returns target.get('value') as the annotation value.  If a tagset uses
       another type of element, this function needs to be re-implemented.

       Parameters:
         event - The event object
         target - The element that triggered the event

       Returns:
         An object with the following properties:
           cls - Class of the annotation that is being changed
           value - New value of that annotation
     */
    handleEvent: function(event, target) {
        return {cls: this.class, value: target.get('value')};
    },

    /* Function: fill

       Fill the approriate elements in a <tr> with annotation from a token data
       object.

       Parameters:
         tr - Table row to fill
         data - An object possibly containing annotations ({anno_pos: ...} etc.)
     */
    fill: function(tr, data) {
        return;
    },

    /* Function: update

       Triggered method to call whenever an annotation changes.  Allows the
       Tagset class to react to a change and store the result.

       Parameters:
         tr - Table row where the change happened
         data - An object possibly containing annotations ({anno_pos: ...}),
                in the state *before* the update
         changes - An object containing any changed values *after* the update
         cls - Tagset class of the annotation
         value - New value of the annotation
     */
    update: function(tr, data, changes, cls, value) {
        return;
    }
});
