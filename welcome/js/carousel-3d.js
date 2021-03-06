/*! carousel-3d - v0.2.0 - 2015-03-13
 * Copyright (c) 2015 PAIO co.,Ltd.; Licensed MIT */
"use strict";
! function a(b, c, d) {
    function e(g, h) {
        if (!c[g]) {
            if (!b[g]) {
                var i = "function" == typeof require && require;
                if (!h && i) return i(g, !0);
                if (f) return f(g, !0);
                var j = new Error("Cannot find module '" + g + "'");
                throw j.code = "MODULE_NOT_FOUND", j
            }
            var k = c[g] = {
                exports: {}
            };
            b[g][0].call(k.exports, function(a) {
                var c = b[g][1][a];
                return e(c ? c : a)
            }, k, k.exports, a, b, c, d)
        }
        return c[g].exports
    }
    for (var f = "function" == typeof require && require, g = 0; g < d.length; g++) e(d[g]);
    return e
}({
    1: [function(a) {
        ! function() {
            "use strict";
            var b = window.jQuery,
                c = a("./ChildrenWrapper"),
                d = a("./Child"),
                e = function(a) {
                    this.el = a, this._makeOption();
                    var d = b(a).children(),
                        e = new c(this),
                        f = 0;
                    this.appendChildrenWrapper(e), d.each(function(a, c) {
                        b(c).attr("selected") && (f = a), this.appendChild(c)
                    }.bind(this)), this._prevButton = b("<div data-prev-button></div>")[0], b(this.el).append(this._prevButton), b(this._prevButton).click(this.prev.bind(this)), this._nextButton = b("<div data-next-button></div>")[0], b(this.el).append(this._nextButton), b(this._nextButton).click(this.next.bind(this)), this.rotate(f)
                };
            e.prototype.el = null, e.prototype.option = {
                animationDuration: 1e3
            }, e.prototype._makeOption = function() {
                (function() {
                    var a = b("<div data-children-wrapper></div>").hide().appendTo(this.el),
                        c = b("<div data-child></div>").hide().appendTo(a).css("transition-duration");
                    a.remove(), c && (c.indexOf("ms") > 0 ? this.option.animationDuration = parseInt(c) : c.indexOf("s") > 0 && (this.option.animationDuration = 1e3 * parseInt(c)))
                }).bind(this)()
            }, e.prototype.appendChild = function(a) {
                this._childrenWrapperObj.appendChild(new d(this._childrenWrapperObj, a))
            }, e.prototype.appendChildrenWrapper = function(a) {
                this._childrenWrapperObj = a, b(this.el).append(a.el)
            }, e.prototype.rotate = function(a) {
                for (var c = this._childrenWrapperObj.numChildren(), d = Math.floor(this._childrenWrapperObj.currentIndex() - c / 2), e = Math.ceil(this._childrenWrapperObj.currentIndex() + c / 2); d > a;) a += c;
                for (; a > e;) a -= c;
                this._childrenWrapperObj.rotate(a), window.setTimeout(function() {
                    for (var c = a; 0 > c;) c += this._childrenWrapperObj.numChildren();
                    b(this.el).trigger("select", c % this._childrenWrapperObj.numChildren())
                }.bind(this), this.option.animationDuration)
            }, e.prototype.prev = function() {
                this.rotate(this._childrenWrapperObj.currentIndex() - 1)
            }, e.prototype.next = function() {
                this.rotate(this._childrenWrapperObj.currentIndex() + 1)
            }, b.fn.Carousel3d = function() {
                var a, b = this,
                    c = arguments[0],
                    d = Array.prototype.slice.call(arguments, 1),
                    f = b.length,
                    g = 0;
                for (g; f > g; g += 1)
                    if ("object" == typeof c || "undefined" == typeof c ? b[g].Carousel3d = new e(b[g], c) : a = b[g].Carousel3d[c].apply(b[g].Carousel3d, d), void 0 !== a) return a;
                return b
            }, b(function() {
                b("[data-carousel-3d]").Carousel3d()
            })
        }()
    }, {
        "./Child": 2,
        "./ChildrenWrapper": 3
    }],
    2: [function(a, b) {
        ! function() {
            "use strict";
            var a = window.jQuery,
                c = window.Modernizr,
                d = function(b, c) {
                    this._childrenWrapperObj = b, this._content = c, this.el = a("<div data-child />")[0], this._frame = a("<div data-child-frame />")[0], this._contentWrapper = a("<div data-content-wrapper />")[0], a(this.el).append(this._frame), a(this._frame).append(this._contentWrapper), a(this._contentWrapper).append(c), this._hideUntilLoad()
                };
            d.prototype._childrenWrapperObj = null, d.prototype._content = null, d.prototype.el = null, d.prototype._contentWrapper = null, d.prototype._hideUntilLoad = function() {
                a(this._content).css("visibility", "hidden"), a(this._contentWrapper).waitForImages(function() {
                    setTimeout(function() {
                        this._resize(), a(this._content).resize(this._resize.bind(this)), a(this.el).resize(this._resize.bind(this)), a(this._content).css("visibility", "visible")
                    }.bind(this), 1)
                }.bind(this))
            }, d.prototype._resize = function() {
                a(this._contentWrapper).width(a(this._content).outerWidth()), a(this._contentWrapper).height(a(this._content).outerHeight());
                var b = a(this._frame).outerWidth() - a(this._frame).innerWidth(),
                    d = a(this._frame).outerHeight() - a(this._frame).innerHeight(),
                    e = (a(this.el).innerWidth() - b) / a(this._content).outerWidth(),
                    f = (a(this.el).innerHeight() - d) / a(this._content).outerHeight(),
                    g = Math.min(e, f),
                    h = Math.floor((a(this.el).innerWidth() - b - a(this._content).outerWidth() * g) / 2),
                    i = Math.floor((a(this.el).innerHeight() - d - a(this._content).outerHeight() * g) / 2);
                a(this._frame).width(a(this._content).outerWidth() * g), a(this._frame).height(a(this._content).outerHeight() * g), a(this.el).css("padding-left", h + "px"), a(this.el).css("padding-top", i + "px"), c.csstransforms ? (a(this._contentWrapper).css("transform", "scale(" + g + ")"), a(this._contentWrapper).css("-ms-transform", "scale(" + g + ")"), a(this._contentWrapper).css("-moz-transform", "scale(" + g + ")"), a(this._contentWrapper).css("-webkit-transform", "scale(" + g + ")")) : (a(this._contentWrapper).css("filter", "progid:DXImageTransform.Microsoft.Matrix(M11=" + g + ", M12=0, M21=0, M22=" + g + ', SizingMethod="auto expand")'), a(this._contentWrapper).css("-ms-filter", "progid:DXImageTransform.Microsoft.Matrix(M11=" + g + ", M12=0, M21=0, M22=" + g + ', SizingMethod="auto expand")'))
            }, b.exports = d
        }()
    }, {}],
    3: [function(a, b) {
        ! function() {
            "use strict";
            var a = window.jQuery,
                c = function(b) {
                    this._carousel3dObj = b, this.el = a("<div data-children-wrapper></div>")[0], a(b.el).resize(this._resize.bind(this))
                };
            c.prototype.el = null, c.prototype._carousel3dObj = null, c.prototype._childObjArray = [], c.prototype._currentIndex = 0, c.prototype._tz = 0, c.prototype._spacing = .05, c.prototype.currentIndex = function(a) {
                return "undefined" == typeof a || "object" == typeof a || isNaN(a) || (this._currentIndex = a), this._currentIndex
            }, c.prototype._resize = function() {
                this._tz = a(this.el).outerWidth() / 2 / Math.tan(Math.PI / this._childObjArray.length), this.rotate(this._currentIndex)
            }, c.prototype.appendChild = function(b) {
                this._childObjArray.push(b), a(this.el).append(b.el), this._resize()
            }, c.prototype.numChildren = function() {
                return this._childObjArray.length
            }, c.prototype.rotate = function(b) {
                this.currentIndex(b);
                var c = 360 / this._childObjArray.length,
                    d = 0,
                    e = 0;
                if (Modernizr.csstransforms3d)
                    for (d = 0; d < this._childObjArray.length; d += 1) {
                        e = c * (d - b);
                        var f = "";
                        f += " translateZ(" + -this._tz * (1 + this._spacing) + "px)", f += " rotateY(" + e + "deg)", f += " translateZ(" + this._tz * (1 + this._spacing) + "px)", a(this._childObjArray[d].el).css("transform", f), a(this._childObjArray[d].el).css("-ms-transform", f), a(this._childObjArray[d].el).css("-moz-transform", f), a(this._childObjArray[d].el).css("-webkit-transform", f), a(this._childObjArray[d].el).css("opacity", Math.cos(Math.PI / 180 * e)), a(this._childObjArray[d].el).css("z-index", Math.floor(100 * (Math.cos(Math.PI / 180 * e) + 1)))
                    } else {
                        var g = a(this.el).width(),
                            h = a(this.el).height(),
                            i = function(b, d) {
                                if ("_degree" === d.prop) {
                                    var e = Math.sin(Math.PI / 180 * b),
                                        f = Math.cos(Math.PI / 180 * b),
                                        i = c / 2,
                                        j = Math.abs(Math.sin(Math.PI / 180 * (b + i)) - Math.sin(Math.PI / 180 * (b - i))) / (2 * Math.sin(Math.PI / 180 * i)) * f,
                                        k = (f + 1) / 2,
                                        l = (j + 1) / 2,
                                        m = (e * g / 2 + g * l / 2 * e) / 2;
                                    a(d.elem).css("z-index", Math.floor(100 * (f + 1))), Modernizr.csstransforms ? (a(d.elem).css("left", m + "px"), a(d.elem).css("opacity", f), a(d.elem).css("transform", "scale(" + l + ", " + k + ")"), a(d.elem).css("-ms-transform", "scale(" + l + ", " + k + ")"), a(d.elem).css("-moz-transform", "scale(" + l + ", " + k + ")"), a(d.elem).css("-webkit-transform", "scale(" + l + ", " + k + ")")) : (a(d.elem).css("top", Math.floor((h - h * k) / 2) + "px"), a(d.elem).css("left", (g - g * l) / 2 + m + "px"), a(d.elem).css("filter", "progid:DXImageTransform.Microsoft.Matrix(M11=" + l + ", M12=0, M21=0, M22=" + k + "), progid:DXImageTransform.Microsoft.Alpha(Opacity=" + 100 * f + ")"), a(d.elem).css("-ms-filter", "progid:DXImageTransform.Microsoft.Matrix(M11=" + l + ", M12=0, M21=0, M22=" + k + "), progid:DXImageTransform.Microsoft.Alpha(Opacity=" + 100 * f + ")"))
                                }
                            };
                        for (d = 0; d < this._childObjArray.length; d += 1) e = c * (d - b), a(this._childObjArray[d].el).animate({
                            _degree: e
                        }, {
                            duration: this._carousel3dObj.option.animationDuration,
                            step: i.bind(this)
                        })
                    }
            }, b.exports = c
        }()
    }, {}]
}, {}, [1]),
function() {
    "use strict";
    var a = jQuery.fn.resize;
    jQuery.fn.resize = function(b) {
        var c = jQuery(this).width(),
            d = jQuery(this).height();
        a.call(this, function() {
            (jQuery(this).width() !== c || jQuery(this).height() !== d) && (c = jQuery(this).width(), d = jQuery(this).height(), b(this))
        }.bind(this))
    }
}();