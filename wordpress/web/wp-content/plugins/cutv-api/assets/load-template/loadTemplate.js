var LoadTemplate = function(element, template, data) {
    // Check if parenet element is defined as string or object.
    if (typeof element == 'string') {
        this.el = document.getElementById(element);
    } else {
        this.el = element;
    }

    // Store template name and data.
    this.tempName = template;
    this.data = data || null;

    // You can change this to path of your template folder.
    this.folderPath = '/wp-content/plugins/cutv-api/views/';
};

LoadTemplate.prototype.create = function(callback) {
    var req = new XMLHttpRequest();
    var that = this;

    // Define parameters for request.
    req.open('get', this.folderPath + this.tempName + '.hbs', true);

    // Wait for request to complete.
    req.onreadystatechange = function() {
        if (req.readyState == 4 && req.status == 200) {
            //Compile HB template, add data (if defined) and place in parent element.
            var compiled = Handlebars.compile(req.response);
            that.el.innerHTML = compiled(that.data);

            // Execute callback function
            if (!!callback) callback();
        }
    };

    // Send request.
    req.send();
};

LoadTemplate.prototype.createAndWait = function(callback) {
    var req = new XMLHttpRequest();
    var that = this;

    // Define parameters for request.
    req.open('get', this.folderPath + this.tempName + '.hbs', true);

    // Wait for request to complete.
    req.onreadystatechange = function() {
        if (req.readyState == 4 && req.status == 200) {
            //Compile HB template, but wait..
            var compiled = Handlebars.compile(req.response);

            // Execute callback function and parse variables.
            callback(compiled, that.el);
        }
    };

    // Send request.
    req.send();
};