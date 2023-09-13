window.Irisio = new function (window) {

/******* polyfill.localStorage *******/
if (window.localStorage == null) {
  window.localStorage = new function () {

    this.getItem = function (key) {
      const cookies = (window.cookie || '').split('; ');
      for (var i = 0; i < cookies.length; i += 1) {
        if (~cookies[i].indexOf(key + '='))
          return decodeURIComponent(cookies[i].slice(cookies[i].indexOf('=') + 1));
      }
      return null;
    };

    this.setItem = function (key, value) {
      const expires = new Date();
      expires.setFullYear(expires.getFullYear() + 1);
      window.cookie = key + '=' + encodeURIComponent(value) + '; expires=' + expires.toString()
    };

    this.removeItem = function (key) {
      const expires = new Date();
      expires.setFullYear(expires.getFullYear() - 1);
      window.cookie = key + '=; expires=' + expires.toString()
    };

  }();
}


/******* polyfill.Array *******/
if (!('indexOf' in Array.prototype)) {
  Array.prototype.indexOf = function(find, i /*opt*/) {
    if (i === undefined)
      i = 0;
    if (i < 0)
      i += this.length;
    if (i < 0)
      i = 0;
    for (var n = this.length; i < n; i++)
      if (i in this && this[i] === find)
        return i;
    return -1;
  };
}

if (!('inArray' in Array.prototype)) {
  Array.prototype.inArray = function(value) {
    var i;
    for (i = 0; i < this.length; i++) {
      if (this[i] == value) {
        return true;
      }
    }
    return false;
  };
}

if (!Array.prototype.map) {
  Array.prototype.map = function(callback/*, thisArg*/) {
    var T, A, k;
    if (this == null) throw new TypeError('this is null or not defined');
    var O = Object(this);
    var len = O.length >>> 0;
    if (typeof callback !== 'function') throw new TypeError(callback + ' is not a function');
    if (arguments.length > 1) T = arguments[1];
    A = new Array(len);
    k = 0;
    while (k < len) {
      var kValue, mappedValue;
      if (k in O) {
        kValue = O[k];
        mappedValue = callback.call(T, kValue, k, O);
        A[k] = mappedValue;
      }
      k++;
    }
    return A;
  };
}

if (!Array.prototype.reduce) {
  Array.prototype.reduce = function(callback /*, initialValue*/) {
    if (this === null)
      throw new TypeError( 'Array.prototype.reduce called on null or undefined' );
    if (typeof callback !== 'function')
      throw new TypeError( callback + ' is not a function');
    var o = Object(this);
    var len = o.length >>> 0;
    var k = 0;
    var value;
    if (arguments.length >= 2) {
      value = arguments[1];
    } else {
      while (k < len && !(k in o))
        k++;
      if (k >= len)
        throw new TypeError( 'Reduce of empty array with no initial value' );
      value = o[k++];
    }
    while (k < len) {
      if (k in o) value = callback(value, o[k], k, o);
      k++;
    }
    return value;
  }
}

/******* util.Irisio *******/
function noop() {}

function jsonp(url, callback) {
  const script = document.createElement('script');
  const callbackId = ('_' + (Math.random() * Math.random()).toString().slice(2)).slice(0, 6);
  window[callbackId] = function (data) {
    delete window[callbackId];
    document.body.removeChild(script);
    return callback(data);
  };
  script.async = true;
  document.body.appendChild(script);
  const appendToken = url.indexOf('?') === -1 ? '?' : '&';
  script.src = url + appendToken + 'callback=' + callbackId
}

function _(sentense, context) {
  const params = Array.prototype.slice.call(arguments, 2);
  const lang = (typeof X_LANG !== 'undefined' ? X_LANG : null) || 'fr';
  const value = Irisio.i18n.get(sentense, context, lang);
  return value.replace(/%s/g, function () {
    return params.shift();
  }) + (params.length > 0 ? params.join(' ') : '');
}

function getMainDomainFromURL(url) {
  const link = document.createElement('a');
  link.href = url;
  return link.hostname.replace(/^www./, '');
}


/******* class.UserAgreements *******/
const UserAgreements = function (scopes) {
  this.scopes = scopes;
};

UserAgreements.prototype.displayAcceptForm = function (type, onAccept, onReject) {
  if (onAccept == null) onAccept = noop;
  if (onReject == null) onReject = noop;
  if (this.hasAccepted(type)) {
    return onAccept();
  } else {
    const self = this;
    var forAll = true;
    const forAllId = Math.random();
    const content = (
      $('<div class="third-part-content-accept-form" />')
        .css({ background: 'white', 'border-radius': '5px', padding: '1em', maxWidth: '600px', margin: '0 auto' })
        .append
        ( $('<div class="text" />').text(_('La lecture de cette vidéo peut entraîner le dépôt d\'un cookie par %s sur votre ordinateur.', _, type)).prepend('<img class="illu_video" src="/img/css/cookie.svg" />')
        , $('<div class="actions" />').append
          ( $('<button class="reject" type="button" />')
            .text(_('Refuser'))
            .on('click', function () {
              $.magnificPopup.close();
              onReject();
            })
          , $('<button class="accept" type="button" />')
            .text(_('Accepter'))
            .on('click', function () {
              if (forAll) self.saveAcceptation(type);
              $.magnificPopup.close();
              onAccept();
            })
          )
        , $('<div class="options" />').append
          ( $('<input type="checkbox" />')
            .attr('checked', forAll)
            .attr('id', forAllId)
            .on('change', function () { forAll = this.checked })
          , $('<label />').attr('for', forAllId).text(_('Oui pour tous les contenus %s', _, type))
          )
        )
    );
    $.magnificPopup.open({ items: [ { src: content, type: 'inline' } ] });
  }
};

UserAgreements.prototype.displayRevokeForm = function (type, onDone) {
  if (onDone == null) onDone = noop;
  if (this.hasAccepted(type)) {
    const self = this;
    return (
      $('<div class="third-part-content-revoke-form" />')
        //.css({ width: '900px', margin: '0 auto' })
		.css({ width: '900px', maxWidth: '100%', margin: '0 auto' })
        .append
        ( $('<button class="revoke" type="button" />')
          .text(_('Retirer l\'autorisation pour %s', _, type))
          .on('click', function () {
            self.revokeAcceptation(type);
            onDone();
          })
        )
    );
  } else {
    return onDone();
  }
};

UserAgreements.prototype.hasAccepted = function (type) {
  return !!window.localStorage.getItem(this.scopes[type]);
};

UserAgreements.prototype.saveAcceptation = function (type) {
  return window.localStorage.setItem(this.scopes[type], 'yes');
};

UserAgreements.prototype.revokeAcceptation = function (type) {
  return window.localStorage.removeItem(this.scopes[type]);
};



/******* class.I18n *******/
const I18n = function () {
  this.translations = {};
};

I18n.prototype.get = function (sentense, context, lang) {
  if (typeof context !== 'string') context = 'all';
  if (this.translations[sentense] == null) return sentense;
  if (this.translations[sentense][context] == null) return sentense;
  if (this.translations[sentense][context][lang] == null) return sentense;
  return this.translations[sentense][context][lang];
};

I18n.prototype.when = function (sentense, context) {
  if (context == null) context = 'all';
  if (this.translations[sentense] == null) {
    this.translations[sentense] = {};
  }
  if (this.translations[sentense][context] == null) {
    this.translations[sentense][context] = {};
  }
  const self = this.translations[sentense][context];
  const chain = { add: function (lang, translation) {
    self[lang] = translation;
    return chain;
  } };
  return chain;
};


/******* class.FormEntry *******/
const FormEntry = function (entry) {
  this.entry  = entry;
  this.checks = [];

  const $entry = $(entry);
  const options =
    { getValue:   $entry.data('get-value')
    , validIf:    $entry.data('valid-if')
    , setError:   $entry.data('set-error')
    , resetError: $entry.data('reset-error')
    };

  // Get Value
  if (typeof options.getValue === 'function') {
    this.getValue = options.getValue;
  } else if (typeof options.getValue === 'string') {
    if (options.getValue.indexOf('$entry') !== -1) {
      const getter = new Function('$entry', options.getValue);
      this.getValue = function () { return getter($(this.entry)); };
    } else {
      this.getValue = function () { return $(this.entry).find(options.getValue).val(); };
    }
  }

  // Check Value
  if (options.validIf != null) {
    if (!(options.validIf instanceof Array)) {
      options.validIf = [options.validIf];
    }
    for (var i = 0; i < options.validIf.length; i += 1) {
      const test = options.validIf[i];
      if (test == null) continue ;
      if (typeof test === 'function') {
        this.checks.push(test);
      } else if (test instanceof RegExp) {
        this.checks.push(function (value) {
          regexp.lastIndex = 0;
          return test.test(value);
        });
      } else if (typeof test === 'string') {
        if (/^\/.+\/[igm]*$/.test(test)) {
          const endOffset = test.lastIndexOf('/');
          const regexp = new RegExp(test.slice(1, endOffset), test.slice(endOffset + 1));
          this.checks.push(function (value) {
            regexp.lastIndex = 0;
            return regexp.test(value);
          });
        } else {
          this.checks.push(new Function('value', test));
        }
      }
    }
  }

  // Set Error
  if (typeof options.setError === 'function') {
    this.setError = options.setError;
  } else if (typeof options.setError === 'string') {
    this.setError = function () {
      $(this.entry).find('.error-message').text(options.setError);
    };
  }

  // Reset Error
  if (typeof options.resetError === 'function') {
    this.resetError = options.resetError;
  }

};

FormEntry.prototype.getValue = function () {
  const $element      = $(this.entry);
  const $valueField   = $element.find('input,select,textarea').filter(':visible').first();
  const inputType     = $valueField.filter('input').attr('type');
  switch (inputType) {
  case 'radio': {
    const groupname  = $element.find('input[type=radio]').data('groupname');
    const groupval   = $('input[data-groupname='+groupname+']:checked').val()
    const $grouplabels= $('input[data-groupname='+groupname+']').parent('.form-entry');
    $grouplabels.addClass('checked');
    return groupval;
  } break ;
  case 'checkbox': {
    return $element.find('input[type=checkbox]:checked').val();
  } break ;
  case 'file': {
    const input = $valueField.get(0);
    if (input == null) return null;
    return input.multiple ? input.files : input.files[0];
  } break ;
  default: {
    return $valueField.val();
  } break ;
  }
};

FormEntry.prototype.checkValue = function () {
  const value = this.getValue();
  for (var i = 0; i < this.checks.length; i += 1) {
    if (this.checks[i].call(this, value) !== true)
      return false;
  }
  return true;
};

FormEntry.prototype.setError = function () {
  $(this.entry).find('.error-message').text(_('Ce champ n\'est pas valide'));
};

FormEntry.prototype.resetError = function () {
  $(this.entry).find('.error-message').empty();
};


/******* Irisio *******/
(function () {
  if (window.Irisio != null) return ;

  const hooks = [];

  this.onReady = function (hook) {
    hooks.push(hook);
  };

  this.overload = function ($document) {
    for (let i = 0 ; i < hooks.length; i += 1)
      hooks[i]($document);
  };

}).call(this);


/******* widget.Slideshow *******/
/*
    <div class="widget-Slideshow"
        data-autoplay-delay="3000"
        speed="1500"
        data-space-between="30" 
        data-slide-per-view="4" 
        data-breakpoints='{"320":{"slidesPerView":1},"400":{"slidesPerView":2},"500":{"slidesPerView":3},"800":{"slidesPerView":4}}'
    >
        <ul class="slides-container"
            <li class="slide">      Content 1    </li>
            <li class="slide">      Content 2    </li>
        </ul>
        <div class="pagination-placeholder"></div>
        <div class="swiper-button-prev"></div>
        <div class="swiper-button-next"></div>
    </div>
*/
this.onReady(function ($document) {
	
	setTimeout(function() {     
	
		$document.find('.widget-Slideshow:not(.loaded)').each(function(){
			const $this                     = $(this).addClass('loaded').addClass('swiper-container');
			const $container                = $this.find('.slides-container').addClass('swiper-wrapper');
			const $slides                   = $container.find('.slide').addClass('swiper-slide');
			if (!($slides.length >= 2)) return;
			const $paginationPlaceholder    = $this.find('.pagination-placeholder').addClass('swiper-pagination');
			const $nextEl					= $this.parent().find('.swiper-button-next');
			const $prevEl					= $this.parent().find('.swiper-button-prev');
			const autoplayDelay             = Number($this.data('autoplay-delay')) || 3000;
			const slideperview              = Number($this.data('slide-per-view')) || 1;
			const spaceBetween              = Number($this.data('space-between')) || 0;
			const breakpoints               = $this.data('breakpoints') || {800:{slidesPerView:slideperview,}};
			const speed                     = Number($this.data('speed')) || 1500;
			const swiper = new Swiper(this, {
				loop:       true,
				grabCursor: true,
				speed:      speed,
				autoplay:   { delay: autoplayDelay },
				slidesPerView : slideperview,
				spaceBetween: spaceBetween,
				pagination:{
					el: $paginationPlaceholder[0],
					type: 'bullets',
					clickable: true,
				},
				navigation: {
					nextEl: $nextEl[0],
					prevEl: $prevEl[0],
				},
				breakpoints: breakpoints,
			})
		});
	
	}, 1);
});

/******* widget.Select2 *******/
/*
<select name="prix" class="widget-Select2" 
    data-placeholder="Prix" 
    data-group="#liste_projets > a>"
    data-on-total-changed="$('#filtre_biens .nb_result').text(count + ' projet' + (count &gt; 1 ? 's' : '')); $('.aucun_resultat')[count == 0 ? 'show' : 'hide'](); console.log(count);">
    data-allowclear="true"
>
        <option value=""></option>
        <option value="1">de 0 à 100 000</option>
        <option value="2">de 100 000 à 200 000¬</option>
</select>
*/

this.onReady(function ($document) {

  $document.find('.widget-Select2:not(.loaded)').each(function () {
    const $this = $(this).addClass('loaded').addClass('select2');
    const $allowclear  = $(this).data('allowclear');// == 'true' ? true : false;

    $this.select2({ minimumResultsForSearch: Infinity, allowClear: $allowclear });

    $this.on('change', function () {
      const $select = $(this);
      const value   = $select.val();
      const name    = $select.attr('name');
      const group   = $select.data('group');

      const $group = $(group).each(function () {
        const $self = $(this);
        const props = $self.data('props');
        const className = 'matchfail-' + name;
        if (value != '') {
          if (props && ~props.indexOf(value))
            $self.removeClass(className);
          else
            $self.addClass(className);
        } else {
          $($self.attr('class').split(/\s+/)).each(function () {
            const className = String(this);
            if (/^matchfail-/.test(className))
              $self.removeClass(className);
          });
        }
        // Change les états visible / caché des éléments filtrés
          const isVisible      = $self.is(':visible');
          const shouldBeHidden = $self.is('[class*=matchfail-]');
          const stateChanged   = isVisible && shouldBeHidden || !isVisible && !shouldBeHidden;
          if (!stateChanged) return ;
          if (shouldBeHidden) {
            $self.hide();
            // "$('#map_canvas')[0].leaflet_markers[$(this).data('markerid')].remove()";
            const onHide = new Function('', $self.data('onHide') || '');
          try { onHide.call($self[0]); } catch (e) { console.error(e); }
          } else {
            $self.show();
            // "const m = $('#map_canvas')[0]; m.leaflet_markers[$(this).data('markerid')].addTo(m.leaflet_map)";
            const onShow = new Function('', $self.data('onShow') || '');
          try { onShow.call($self[0]); } catch (e) { console.error(e); }
          }
        });

      const displayedItemsCount = $group.filter(':visible').length;
      //const f = "$('#filtre_biens .nb_result').text(count + ' projet' + (count > 1 ? 's' : ''))";
      const onTotalChanged = new Function('count', $select.data('onTotalChanged') || '');
      try { onTotalChanged.call(this, displayedItemsCount); } catch (e) { console.error(e); }
      });

    const $form = $this.parents('form');
    const $resetButton = $form.find('input[type="reset"]');
    if ( $resetButton.length > 0 ) {
      $resetButton.on('click', function (ev) {
        $this.val(null).trigger('change');
      });
    }

  });
});

/******* widget.PopinGallery *******/
// https://v5.irisio.fr/test.3-48-3-4.php

this.onReady(function ($document) {

  $document.find('a:not(.skip-widget-PopinGallery)').filter(function () {
    if (this.origin == window.location.origin) {
      if (/\/[^-]+-40-(\d+)[^\d]/.exec(this.pathname))
        $(this).addClass('widget-PopinGallery');
    }
  });

  // add popin gallery on gallery pages
  $document.find('.widget-PopinGallery:not(.loaded)').each(function () {
    const $this = $(this).addClass('loaded');
    $this
      .addClass('v_galerie')
      .css('display', 'inline-block')
      .on('click', function (ev) {
        const urlMatch = /\/[^-]+-40-(\d+)[^\d]/.exec(this.pathname);
        if (urlMatch == null) return ;
        ev.preventDefault();
        const slideshowId = urlMatch[1];
        const slideshowContentURL = '/jx.gallerie-54-' + slideshowId + '-json.php';
        $.getJSON(slideshowContentURL, function (data) {
          $.magnificPopup.open({
            items: data,
            type: 'image',
            tLoading: _('Chargement de l\'image') + ' #%curr%...',
            tClose: _('Fermer (Touche : Echap)'),
            mainClass: 'mfp-img-mobile',
            gallery: {
              enabled: true,
              navigateByImgClick: true,
              tPrev: _('Précédente (Touche : Flèche gauche)'),
              tNext: _('Suivante (Touche : Flèche droite)'),
              tCounter: '<span class="mfp-counter">%curr%/%total%</span>'
            },
            image: {
              tError: '<a href="%$url%">' + _('Erreur de chargement de l\'image') + ' #%curr%</a>.',
              titleSrc: function () { return 'title'; }
            },
            ajax: {
              tError: '<a href="%$url%">' + _('Erreur de chargement du contenu') + ' %$url%</a>.'
            }
          });
        });
      });

  });
});


/******* widget.FormChecking *******/
/*

*/

/******* widget.MobileNavigationMenu *******/
/*
  <div class="widget-MobileNavigationMenu">
    <button class="menu-toggle-button">Afficher / Cacher le menu</button>
    <div class="menu-content"></div>
  </div>

*/

this.onReady(function ($document) {

  $(window).resize(function (ev) {
	/*
	const $widget = $document.find('.widget-MobileNavigationMenu.loaded');
    const $toggleButton = $widget.find('.menu-toggle-button');
    if ($(window).width() <= 991) {
      //$toggleButton.show();
    } else {
      //$toggleButton.hide();
      //$widget.css({ width: 'initial', height: 'initial' });
    }
	*/
  });

/*
  $document.find('.widget-MobileNavigationMenu:not(.loaded)').each(function () {
    const $this = $(this).addClass('loaded').addClass('closed');
    const $toggleButton = $this.find('.menu-toggle-button').attr('id', 'btn_menu');
    const $content = $this.find('.menu-content').attr('id', 'content_menu');

    //if ($(window).width() <= 991) $toggleButton.show();

    $toggleButton.on('click', function (ev) {
      const isMenuClosed = $this.hasClass('closed');
      if (isMenuClosed) {
        $this.addClass('opening').removeClass('closed');
        //$content.slideDown(function () {
          $this.addClass('opened').removeClass('opening');
        //});
      } else {
        $this.addClass('closing').removeClass('opened');
        //$content.slideUp(function () {
          $this.addClass('closed').removeClass('closing');
        //});
      }
    });

  });
*/


	if ($('.menu-toggle-button')[0]) {
        $('.menu-toggle-button, #skip_menu').click(function() {
            if ($('.menu-toggle-button').is(':visible')) {
                $(".menu-content").slideToggle({
                    /*start: function() {
                        $(this).css({
                            display: "flex"
                        })
                    }
					*/
                });
            }
        });
        $(window).resize(function() {
            if ($(window).width() >= 992) {
                $('.menu-content').removeAttr('style');
            }
            Menus();
        });
    }
	Menus();
});


var toggle = false;
var Menus = function() {
    if (window.innerWidth < 992 && toggle == false) {
        toggle = true;
        $('#m2 ul').siblings('a').off('click').on('click', function(event) {
            var child2n = $(this).next('ul').children('li').length;
            var doSlideDown = !$(this).hasClass('active');
            
            if (child2n > 1) {
                event.preventDefault();
				
				//$('#m2 li ul').slideUp();
				$(this).parent('li').siblings('li').find('ul').slideUp().siblings('a').removeClass('active');
				//$('#conteneur_menus').slideUp();
				//$('#m2 li>a').removeClass('active');
				
				if (doSlideDown) {
					$(this).addClass('active');
					//$(this).parents('li').toggleClass('border');
					$(this).siblings('ul').slideDown();
				}
				else{
					$(this).removeClass('active').siblings('ul').slideUp();
				}
            }
        });
    }
    else if (window.innerWidth >= 992) {
        toggle = false;
        $('#m2 ul').siblings('a').off('click');
        $('#m2 ul,#main_menu').removeAttr('style');
    }
}	

/******* widget.GeoMap *******/
// https://v5.irisio.fr/contact-4.php
/*
<div class="widget-GeoMap" data-latitude="42.696145" data-longitude="2.879732" data-zoom="9">
  <div class="marker" data-latitude="42.696145" data-longitude="2.879732">
    <strong>Titre du marker</strong>
  </div>
</div>

*/

this.onReady(function ($document) {

  $document.find('.widget-GeoMap:not(.loaded)').each(function () {
    const $this     = $(this).addClass('loaded');
    const latitude  = $this.data('latitude') || 42.696145;
    const longitude = $this.data('longitude') || 2.879732;
    const zoom      = $this.data('zoom') || 9;

    const mymap = L.map(this,{worldCopyJump:true}).setView([latitude, longitude], zoom);
    const listePoints   = []; //tableau des lattitudes et longitudes pour centrer la carte
    const markers       = {}; //tableau qui stock tous les markeurs

    this.leaflet_map     = mymap;
    this.leaflet_markers = markers;

    L.tileLayer( 
        'https://{s}.tile.openstreetmap.fr/osmfr/{z}/{x}/{y}.png',
        {minZoom: 4, maxZoom: 19, attribution: '&copy; <a href="https://openstreetmap.org/copyright">OpenStreetMap contributors</a>'}
    ).addTo(mymap);

    const customIcon = L.icon({
      iconUrl: $this.data('icon-url') || '/img/css/marker.svg',
      //shadowUrl: 'marker-shadow.png',
      iconSize: [30, 44], // size of the icon
      //shadowSize:   [50, 64], // size of the shadow
      iconAnchor: [15, 44], // point of the icon which will correspond to marker's location
      //shadowAnchor: [4, 62],  // the same for the shadow
      popupAnchor: [0, -44] // point from which the popup should open relative to the iconAnchor
    });
    /* IRISIO
    const customIcon = L.icon({
      iconUrl: $this.data('icon-url') || '/img/css/marker.32x37.png',
      iconSize: [32, 37],
      iconAnchor: [16, 37],
      popupAnchor: [0, -37]
    });    
    */

    const customIconHover = L.icon({
      iconUrl: $this.data('icon-url') || '/img/css/marker_hover.svg',
      //shadowUrl: 'marker-shadow.png',
      iconSize: [30, 44], // size of the icon
      //shadowSize:   [50, 64], // size of the shadow
      iconAnchor: [15, 44], // point of the icon which will correspond to marker's location
      //shadowAnchor: [4, 62],  // the same for the shadow
      popupAnchor: [0, -44] // point from which the popup should open relative to the iconAnchor
    });

    $this.find('.marker').each(function(){
      const $this = $(this).detach();
      const markerid = $this.data('markerid') || String(Math.random);

      markers[markerid] = L.marker(
                [ $this.data('latitude'), $this.data('longitude') ],
                {
                        icon: customIcon,
                        bounceOnAdd: true,
                        bounceOnAddOptions: { duration: 500, height: 100, loop: 1 },
                        bounceOnAddCallback: function () {},
                        uniqueid : markerid
        }
      )
        .addTo(mymap)
        .bindPopup($this.html());

      listePoints.push([$this.data('latitude'), $this.data('longitude')]);
    });

    if( $(listePoints).length >= 1 ) {
      mymap.fitBounds(listePoints, { padding: [10, 10], maxZoom: 13 });
    }

    $this.find('.picker').first().each(function () {
      const $picker = $(this);
      const $longitudeInput = $picker.find('.longitude');
      const $latitudeInput = $picker.find('.latitude');
      const longitude = $longitudeInput.val();
      const latitude  = $latitudeInput.val();

      const icon = L.icon({
        iconUrl:     $picker.data('icon-url') || '/img/css/geoloc.target.png',
        iconSize:    [32, 32], // size of the icon
        iconAnchor:  $picker.data('icon-anchor') || [16, 16], // picker pixel
        popupAnchor: [0, 0]    // point from which the popup should open relative to the iconAnchor
      });

      const picker = L.marker
      ( new L.LatLng(Number(latitude), Number(longitude))
      , { icon:      icon
        , draggable: true
        }
      ).addTo(mymap);

      picker.on('drag', function (ev) {
        const position = picker.getLatLng();
        $longitudeInput.val(position.lng);
        $latitudeInput.val(position.lat);
      });

      mymap.doubleClickZoom.disable();
      mymap.on('dblclick', function (ev) {
        var position = new L.LatLng(ev.latlng.lat, ev.latlng.lng);
        picker.setLatLng(position);
        $longitudeInput.val(position.lng);
        $latitudeInput.val(position.lat);
      });

    });

    //changement des icones dans la map au survol de la liste html
    if( $('#map_canvas.interactive').length ) {
      $('#liste_projets .single_projet').on('mouseenter', function(e) {

            for (var cle in markers) {
              if (markers.hasOwnProperty(cle)) {
                const markerItem = markers[cle];
                if (markerItem == null) continue ;
                markerItem.setIcon(customIcon);
                markerItem.setZIndexOffset(1);
              }
            }

            var id = $(this).attr('data-markerid');
            var marker = markers[id];
            if (marker == null) return ;
            marker.setIcon(customIconHover);
            marker.setZIndexOffset(1000);
          });

      $('#liste_projets .single_projet').on('mouseleave', function(e) {
                        /*
                        for (var cle in markers) {
                                if (markers.hasOwnProperty(cle)) {
                                        var marker = markers[cle];
                                        marker.setIcon(customIcon);
                                }
                        }
                        */
        const id = $(this).attr('data-markerid');
        const marker = markers[id];
        if (marker == null) return ;
        marker.setIcon(customIcon);
      });
    }
  });
});


/******* widget.Captcha *******/
// https://v5.irisio.fr/contact-4.php

this.onReady(function ($document) {
  $document.find('#label_email:contains(humain),#label_email:contains(human),#label_email:contains(humano),#label_email:contains(home)').hide();
});


/******* widget.AutoVideoEmbeder *******/
this.onReady(function ($document) {

  $document.find('a[href*="youtu"]:not(.loaded)').each(function () {
    const $this = $(this).addClass('loaded');
//    const urlMatch = /^.{7,16}youtu(?:be)?(?:-nocookie)?\.(?:com|be)\/.*[?&]v=([\w_-]+)(?:$|&)/.exec(this.href);
    const urlMatch = /^(?:https?:\/\/)?(?:www\.)?(?:youtu\.be\/|youtube\.com\/(?:embed\/|v\/|watch\?v=|watch\?.+&v=))([\w_-]{11})(?:\S+)?$/.exec(this.href);
    if (urlMatch == null) return ;
    const videoId = urlMatch[1];
    const videoIdB64 = btoa(videoId);
    $this
      .addClass('v_youtube')
      .html
      ( $('<img />')
        .attr('src', '/im-67-yt-' + videoIdB64 + '-yhq.php')
        .attr('alt', _('Lancer la vidéo'))
        .attr('title', _('Lancer la vidéo'))
      )
      .on('click', function (ev) {
        ev.preventDefault();
        ev.stopImmediatePropagation();
        Irisio.userAgreements.displayAcceptForm('Youtube', function onAccept() {
          const content = (
            $('<iframe class="mfp-iframe" frameborder="0" allow="autoplay" allowfullscreen />')
              //.css({ display: 'block', height: '506px', width: '900px', margin: '0 auto' })
			  .css({ display: 'block', height: '506px', maxHeight : '66vw', width: '900px', maxWidth: '100%', margin: '0 auto' })
              .attr('src', 'https://www.youtube-nocookie.com/embed/' + videoId + '?autoplay=1&rel=0')
              .add(Irisio.userAgreements.displayRevokeForm('Youtube'))
          );
          $.magnificPopup.open({ items: [ { src: content, type: 'inline' } ] });
        }, function onDeny() {
          $.magnificPopup.close();
        });
      });
  });

  $document.find('a[href*="vimeo"]:not(.loaded)').each(function () {
    const $this = $(this).addClass('loaded');
    const urlMatch = /vimeo.com\/(\d+)(?:$|&)/.exec(this.href);
    if (urlMatch == null) return ;
    const videoId = urlMatch[1];
    const $thumbnail = $('<img />')
      .attr('alt', _('Lancer la vidéo'))
      .attr('title', _('Lancer la vidéo'))
    $this
      .addClass('v_vimeo')
      .html
      ( $('<img />')
        .attr('src', '/im-67-vimeo-' + videoId + '-vl.php')
        .attr('alt', _('Lancer la vidéo'))
        .attr('title', _('Lancer la vidéo'))
      )
      .on('click', function (ev) {
        ev.preventDefault();
        ev.stopImmediatePropagation();
        Irisio.userAgreements.displayAcceptForm('Vimeo', function onAccept() {
          const content = (
            $('<iframe class="mfp-iframe" frameborder="0" allow="autoplay" allowfullscreen />')
              //.css({ display: 'block', height: '506px', width: '900px', margin: '0 auto' })
			  .css({ display: 'block', height: '506px', maxHeight : '66vw', width: '900px', maxWidth: '100%', margin: '0 auto' })
              .attr('src', 'https://player.vimeo.com/video/' + videoId + '?autoplay=1')
              .add(Irisio.userAgreements.displayRevokeForm('Vimeo'))
          );
          $.magnificPopup.open({ items: [ { src: content, type: 'inline' } ] });
        }, function onDeny() {
          $.magnificPopup.close();
        });
      });
  });

});

/******* widget.AnimateObject *******/
this.onReady(function ($document) {
  $document.find('.widget-animateObject:not(.loaded)').each(function () {
    const $this = $(this).addClass('loaded');
    const options = $this.data('animationOptions') || {};
    switch ($this.data('animationType')) {
    case 'slide-up': {
      const elementsSelector = $this.data('animationElements');
      const getElements = function () { return $this.find(elementsSelector); };
      const $elements = getElements().css({ position: 'relative', top: 0 });
      const getHeight = function () {
        return Array.prototype.reduce.call($elements, function (accu, item) {
          return Math.max($(item).height(), accu);
        }, 0);
      };
      const height = getHeight();
      if ($elements.length > 1) {
        $this.css({ overflow: 'hidden', height: height });
        setInterval(function () {
          const height = getHeight();
          const $elements = getElements();
          const $first  = $elements.slice(0, 1).css({ height: height });
          const $second = $elements.slice(1, 2).css({ height: height });
          $first.animate({ top: -height }, function () {
            $first.css({ top: 0 });
            $elements.slice(-1).after($first);
          });
          $second.animate({ top: -height }, function () {
            $second.css({ top: 0 });
          });
        }, options.duration || 4000)
      }
    } break ;
    }
  });
});

/******* hook.WelcomePopin *******/
this.onReady(function () {
  if (typeof X_POPIN === 'undefined' || X_POPIN !== true) return ;
  var $h = Math.round(0.85*$(window).height());//width="'+$h+'px" 
  var mfp_x_popin_src = X_POPIN_HREF != '' ? '<div id="x_popin"><a href="'+X_POPIN_HREF+'" target="_blank"><img src="'+X_POPIN_SRC+'" title="'+X_POPIN_TITLE+'"></a></div>' : '<div id="x_popin"><img src="'+X_POPIN_SRC+'" title="'+X_POPIN_TITLE+'"></div>'
  var mfp_x_popin = $.magnificPopup.open({
    items: {
      src: mfp_x_popin_src,
      type: 'inline',
      closeBtnInside:false
    }
  });
});


/******* hook.CopyLink *******/
/*
<a href="###URL A COPIER###" class="hook-CopyLink">Copier le lien</a>
*/

$(window.document).on('click', '.hook-CopyLink', function (ev) {
    ev.preventDefault();
    const $this = $(this);
    const url = $this.attr('href');
    if (url == null) return ;
    var $temp = $("<input>");
    $this.append($temp);
    $temp.val( url ).select();
    document.execCommand("copy");
    $temp.remove();
    var succeed;
    try {
        succeed = document.execCommand("copy");
        $this.css({"background-color":"green", "color":"white"});
    }
    catch(e) {
        succeed = false;
        $this.css({"background-color":"red", "color":"white"});
    }
    //return succeed;
});

/******* hook.AnimateOnAppear *******/
const elements = [];

this.onReady(function ($document) {
  $document.find('[data-animate-on-appear]').each(function () {
    const $this = $(this);
    const type = $this.data('animateOnAppear');
    const options = $this.data('animateOnAppearOptions') || {};
    $this.removeAttr('data-animate-on-appear').removeAttr('data-animate-on-appear-options');
    $this.scrolling();
    $this.css({ visibility: 'hidden' }).on('scrollin', function () {
      $this.off('scrollin');
      runAnimation(this, type, options);
    });
  });
});

function runAnimation(element, type, options) {
  setTimeout(function () {
    const $this = $(element);
    const $window = $(window);
    const view = { width: $window.innerWidth(), height: $window.innerHeight() };
    $this.css({ position: 'relative' });
    switch (type) {
    case 'from-right': {
      $this
        .css({ left: view.width - $this.position().left, visibility: 'visible' })
        .animate({ left: 0 }, options.duration || 400);
    } break ;
    case 'from-left': {
      $this.css({ left: - $this.position().left - $this.width(), visibility: 'visible' })
        .animate({ left: 0 }, options.duration || 400);
    } break ;
    case 'zoom-in': {
      const display = $this.css('display') === 'block' ? 'block' : 'inline-block';
      const $verticalRule = $('<span />')
        .css({ display: 'inline-block', 'vertical-align': 'middle', height: '100%', width: 0 });
      const $holder = $('<div />')
        .css({ width: $this.outerWidth(), height: $this.outerHeight(), textAlign: 'center' })
        .append($verticalRule)
      $this.after($holder);
      $holder.append($this);
      $this
        .css({ 'vertical-align': 'middle', display: 'inline-block', width: 0, height: 0, visibility: 'visible' })
        .animate({ width: '100%', height: '100%' }, options.duration || 400)
    } break ;
    }
  }, options.delay);
}


/******* hook.FormChecking *******/
/*
<form class="hook-FormChecking">

  <div class="form-entry" data-valid-if="/^[^\s@]+@([^\s@.,]+\.)+[^\s@.,]{2,}$/" data-required>
    <label for="email_001">Email</label>
    <input id="email_001" type="text" name="email" />
    <span class="error-message"></span>
  </div>

  <div class="form-entry" data-valid-if="/^\w+$/i">
    <select name="country">
      <option value="">(select a country)</option>
      <option>France</option>
      <option>Germany</option>
    </select>
  </div>

  <div class="form-entry" data-required>
    <div><input type="radio" name="group" value="1" /><label>One</label></div>
    <div><input type="radio" name="group" value="2" /><label>Two</label></div>
  </div>

  <div class="form-entry" data-required>
    <div><input type="checkbox" name="colors[]" value="red" /><label>Red</label></div>
    <div><input type="checkbox" name="colors[]" value="blue" /><label>Blue</label></div>
    <div><input type="checkbox" name="colors[]" value="yellow" /><label>Yellow</label></div>
  </div>

  <div class="form-entry">
    <label for="email_001">Num�ro de t�l�phone</label>
    <input type="text" name="phonenumber"
           data-get-value="return $entry.find('input').val().replace(/[^\d]/g, '');"
           data-valid-if="/^0\d{9}$/"
    />
  </div>

</form>

Validite dependante d'un autre champs : D�fini via injection on-change sur le champs "controlant" :
    $tmp_onchange               =   '$form.find("#label_des_champs_dependant").data("required", value != null);';
    $c[$i]['param']             =   array('valid-if'=>'',  'on-change'=>htmlentities($tmp_onchange),  'set-error'=>'', 'get-value'=>'', 'disabled'=>FALSE);//,'checked'=> 1 ? TRUE : FALSE    

on-click est aussi possible

*/


(function () {

  $(window.document).on('submit', '.hook-FormChecking', function (ev) {
    const $this = $(this).addClass('checking');
    $(':submit').addClass('disabled').parent('label').addClass('disabled');
    var errors = 0;

    $this.find('.form-entry').each(function (){
      const $entry         = $(this);
      if(!$(this).hasClass('checked')){
        const rule           = new FormEntry(this);
        const dataRequired   = $entry.data('required');
        const isRequired     = dataRequired != null && dataRequired !== false;
        const value          = rule.getValue();
        const isValueDefined = value != null ? !/^[\s.\n]*$/.test(value) : false;
        rule.resetError();
        $entry.addClass('checking').removeClass('checking-failed')
        if (isRequired || isValueDefined){
          if (isValueDefined === false || rule.checkValue() === false) {
            rule.setError();
            $entry.addClass('checking-failed');
            errors += 1;
//console.log({
//  $entry:$entry,
//  isValueDefined:isValueDefined,
//  rule:rule,
//  rulecheck:rule.checkValue(),
//  errors:errors,
//});            
          }
        }
        $entry.removeClass('checking').addClass('checked');
      }
    });
    $this.removeClass('checking');    
    if (errors === 0) return ;
    $('html,body').animate({scrollTop:$('label.checking-failed').first().offset().top-50},500)
    ev.preventDefault();
    ev.stopImmediatePropagation();
    $('.form-entry').removeClass('checked');
    $(':submit').removeClass('disabled').parent('label').removeClass('disabled');
  });

  $(window.document).on('change', '.hook-FormChecking .form-entry[data-on-change]', function (ev) {
    const $this = $(this);
    const $form = $this.parents('.hook-FormChecking').first();
    const dataCallable  = $this.data('onChange');
    if (typeof dataCallable === 'string') {
      $this.data('onChange', new Function('event,value,$form', dataCallable));
    }
    const callable = $this.data('onChange');
    const value = new FormEntry(this).getValue();
    return callable.call(this, ev, value, $form);
  });

  $(window.document).on('click', '.hook-FormChecking .form-entry[data-on-click]', function (ev) {
    const $this = $(this);
    const $form = $this.parents('.hook-FormChecking').first();
    const dataCallable  = $this.data('onClick');
    if (typeof dataCallable === 'string') {
      $this.data('onClick', new Function('event,value,$form,formEntry', dataCallable));
    }
    const callable = $this.data('onClick');
    const formEntry = new FormEntry(this);
    const value = formEntry.getValue();
    return callable.call(this, ev, value, $form, formEntry);
  });

})();

/******* hook.PopinRemoteContent *******/
/*
<a class="widget-popin" data-href="/jx.annuaire-63-6-mfp.php">Click Me</a>
==> <a href="#" data-href="{$X_['toc'][68]['url']}" class="btn_partager hook-PopinRemoteContent">{$i18n['partager'][X_LANG]}</a>
*/

$(window.document).on('click', '.hook-PopinRemoteContent', function (ev) {
  const $this = $(this);
  const url = $this.data('href') != null ? $this.data('href') : $this.attr('href');
  if (url == null) return ;
  ev.preventDefault();
  $.magnificPopup.open({
    items: { src: url },
    type: 'ajax',
    ajax: {
      settings: { cache: false },
      tError: '<a href="' + url + '">' + _('Erreur de chargement du contenu', url) + '</a>.'
    },
    callbacks: {
      ajaxContentAdded: function() {
        Irisio.overload($(this.content));
    }
    }
  });
});


/******* hook.Readability *******/
if(X_ELEM != 107){
  $(document).on('mouseover', 'tr', function () {
    $(this).addClass('over');
  });

  $(document).on('mouseout', 'tr', function () {
    $(this).removeClass('over');
  });
}

/******* hook.UncaughtException *******/
$(window).on('error', function (error) {
  const message = error.originalEvent.message;
  reportError(message);
});

$(window).on('unhandledrejection', function (error, promise) {
  debugger;
});

function reportError(message) {
  // FIXME Add debunce to avoid waf ban
  return ;
  const $window = $(window);
  $.ajax
  ( { type: 'post'
    , url: '/report.frontend.error.php'
    , dataType: 'json'
    , data:
      { message: message
      , display: { width: $window.innerWidth(), height: $window.innerHeight() }
      }
    }
  );
}


/******* init.Document *******/
$(function () {
  Irisio.overload($(document));
});

this.userAgreements = new UserAgreements
( { Youtube: 'X_USER_ACCEPT_THIRT_PART_CONTENTS_YOUTUBE'
  , Vimeo: 'X_USER_ACCEPT_THIRT_PART_CONTENTS_VIMEO'
  }
);

const siteDomain = getMainDomainFromURL(window.location);

$(window.document).on('click', 'a', function (ev) {
  if (this.href == null) return ;
  if (!/^https?/.test(this.href)) return ;
  const linkDomain = this.hostname.replace(/^www./, '');
  if (linkDomain == siteDomain) return ;
  ev.preventDefault();
  const jQueryData = $._data(this);
  if (jQueryData.events && jQueryData.events.click && jQueryData.events.click.length > 0) return ;
  window.open(this.href, '', '');
});


/******* init.I18n *******/
this.i18n = new I18n();

this.i18n.when('Rechercher sur le site')
  .add('en', 'Search Site')
  .add('es', 'Búsqueda en el sitio')
  .add('ca', 'Cerca al lloc')
;
this.i18n.when('Veuillez verifier les champs')
  .add('en', 'Please check the fields')
  .add('es', 'Por favor, compruebe los campos')
  .add('ca', 'Si us plau, comproveu els camps')
;
this.i18n.when('Ce champ n\'est pas valide')
  .add('en', 'This field is not valid')
  .add('es', 'Este campo no es valido')
  .add('ca', 'Aquest camp no és vàlid')
;
this.i18n.when('Merci')
  .add('en', 'Thank you')
  .add('es', 'Gracias')
  .add('ca', 'Gràcies')
;
this.i18n.when('Fermer')
  .add('en', 'Close')
  .add('es', 'Cerrar')
  .add('ca', 'Tancar')
;
this.i18n.when('Votre itinéraire vers')
  .add('en', 'Your route towards')
  .add('es', 'La ruta hacia')
  .add('ca', 'La ruta cap')
;
this.i18n.when('Lancer la vidéo')
  .add('en', 'Play')
  .add('es', 'Reproducción de vídeo')
  .add('ca', 'Reproducció de vídeo')
;
this.i18n.when('Chargement de l\'image')
  .add('en', 'Loading image')
  .add('es', 'Cargando imagen')
  .add('ca', 'Carregant imatge')
;
this.i18n.when('Fermer (Touche : Echap)')
  .add('en', 'Close (key: ESC)')
  .add('es', 'Cerrar (clave: Esc)')
  .add('ca', 'Tancar (clau: Esc)')
;
this.i18n.when('Précédente (Touche : Flèche gauche)')
  .add('en', 'Previous (Key: Left Arrow)')
  .add('es', 'Anterior (Clave: Flecha izquierda)')
  .add('ca', 'Anterior (Clau: Fletxa esquerra)')
;
this.i18n.when('Suivante (Touche : Flèche droite)')
  .add('en', 'Next (Key: Right arrow)')
  .add('es', 'Siguiente (Clave: Flecha derecha)')
  .add('ca', 'Següent (Clau: Fletxa dreta)')
;
this.i18n.when('Erreur de chargement de l\'image')
  .add('en', 'Error while loading image')
  .add('es', 'Error al cargar la imagen')
  .add('ca', 'Error en carregar la imatge')
;
this.i18n.when('Erreur de chargement du contenu')
  .add('en', 'Error while loading')
  .add('es', 'Error al cargar contenido')
  .add('ca', 'Error en carregar contingut')
;
this.i18n.when('Accepter')
  .add('en', 'Accept')
  .add('es', 'Aceptar')
  .add('ca', 'Acceptar')
;
this.i18n.when('Refuser')
  .add('en', 'Cancel')
  .add('es', 'Rechazar')
  .add('ca', 'Negar')
;
this.i18n.when('Retirer l\'autorisation')
  .add('en', 'Revoke authorization')
  .add('es', 'Tener desautorización')
  .add('ca', 'Retirar l\'autorització')
;
this.i18n.when('Oui pour tous les contenus %s')
  .add('en', 'Yes for all %s contents')
  .add('es', 'Sí para todo el contenido %s')
  .add('ca', 'Sí per a tot el contingut %s')
;
this.i18n.when('La lecture de cette vidéo peut entraîner le dépôt d\'un cookie par %s sur votre ordinateur.')
  .add('en', 'Playing this video may cause %s to place a cookie on your computer.')
  .add('es', 'Reproducir este video puede hacer que %s coloque una cookie en su computadora.')
  .add('ca', 'La reproducció d\'aquest vídeo pot provocar que %s col·loqui una galeta al vostre ordinador.')
;
this.i18n.when('Retirer l\'autorisation pour %s')
  .add('en', 'Remove permission for %s')
  .add('es', 'Quitar permiso para %s')
  .add('ca', 'Elimina el permís per %s')
;
this.i18n.when('Géolocalisation actuelle')
  .add('en', 'Current geolocation')
  .add('es', 'Geolocalización actual')
  .add('ca', 'Geolocalització actual')
;
this.i18n.when('Rechercher un emplacement')
  .add('en', 'Find a location')
  .add('es', 'Encuentra una ubicación')
  .add('ca', 'Trobeu una ubicació')
;
this.i18n.when('Contributeurs OpenStreetMap')
  .add('en', 'OpenStreetMap contributors')
  .add('es', 'Colaboradores de OpenStreetMap')
  .add('ca', 'Col·laboradors d\'OpenStreetMap')
;

/*
this.i18n.when('')
  .add('en', '')
  .add('es', '')
  .add('ca', '')
;
*/

/******* @old-js-refactoring-file *******/
this.onReady(function () {
	// FONCTIONS //////////////////////////////////////////////////////////
	var initBooth = function(){
	    if($('#ch_booth').val() != 'none'){
//	        $('label.booth_dependant').show();
	        if($('#ch_booth_size').val() == 0){
		        $('#ch_booth_size').find('option:eq(1)').prop('selected', true);
	        }
	    }
	    else{
//	        $('label.booth_dependant').hide();
	        $('label.booth_dependant input,label.booth_dependant textarea').val('');
	        $('#ch_booth_size').find('option:eq(0)').prop('selected', true);
	    }
	};
	var initSponsorship = function(){
		/*
		console.log({
			ch_sponsorship:$('#ch_sponsorship').val(),
			booth_datas:$booth_datas,
			booth_datas_for_this_sp:$booth_datas[ $('#ch_sponsorship').val() ],
			option_text: $('#ch_sponsorship').find("option:selected").text(),
		});
		*/
		var $sponsorship 	= $('#ch_sponsorship').val();
		//var $booth_data 	= $booth_datas[ $sponsorship ]; // Affichage de base = sans réduction
		var $booth_data 	= $booth_datas[ 'none' ];
		$('#ch_booth_size option').each(function( index ){
			var $val 		= $(this).val() || 0;
			var $base		= $enum_booth_size[ $val ];
			var $price		= $booth_data[ $val ]['price'];
			var $discount	= $booth_data[ $val ]['discount'] * -1;
			//var $newtext 	= $base+' : '+$price+' EUR ('+$sponsorship+' discount : '+$discount+')';
			var $newtext 	= $base+' : '+$price+' EUR';
			$(this).text($newtext);
			console.log({
				index:index,
				val:$val,
				base:$base,
				price:$price,
				discount:$discount,
				sponsor:$sponsorship,
				oldtext:$(this).text(),
				newtext:$newtext,
			});
		});
	};
	var initPrices = function(){
		var $sponsorship 				= 	$('#ch_sponsorship').val();
		var $booth_size 				= 	$('#ch_booth_size').val() || 0;
		var $option_charging_station 	= 	$('#ch_option_charging_station').is(':checked') ? 1 : 0;
		var $option_ad_page 			= 	$('#ch_option_ad_page').is(':checked') ? 1 : 0;
		var $booth_data 				= 	$booth_datas[ $sponsorship ];

		var $p_sponsorship				=	$sponsorship_prices[ $sponsorship ];
		//var $p_booth					=	$booth_data[ $booth_size ]['price'];  // Affichage de base = sans réduction
		var $p_booth					=	$booth_datas[ 'none' ][ $booth_size ]['price'];
		var $p_booth_discount			=	$booth_data[ $booth_size ]['discount'] * -1;
		var $p_option_charging_station	=	$option_prices['option_charging_station'] * $option_charging_station;
		var $p_option_ad_page			=	$option_prices['option_ad_page'] * $option_ad_page;
		var $total						=	$p_sponsorship + $p_booth + $p_booth_discount + $p_option_charging_station + $p_option_ad_page;

		$('input#ch_price_sponsorship').val($p_sponsorship+' EUR');
		$('input#ch_price_booth').val($p_booth+' EUR');
		$('input#ch_price_booth_discount').val($p_booth_discount+' EUR');
		$('input#ch_price_option_charging_station').val($p_option_charging_station+' EUR');
		$('input#ch_price_option_ad_page').val($p_option_ad_page+' EUR');
		$('input#ch_total').val($total+' EUR');
		/*
		console.log({
			triggered:'initPrices()',
			sponsorship:$sponsorship,
			booth_size:$booth_size,
			option_charging_station:$option_charging_station,
			option_ad_page:$option_ad_page,
			booth_data:$booth_data,
			p_sponsorship:$p_sponsorship,
			p_booth:$p_booth,
			p_booth_discount:$p_booth_discount,
			p_option_charging_station:$p_option_charging_station,
			p_option_ad_page:$p_option_ad_page,
			total:$total,
		});
		*/
	};
	var initId_produits = function(){
		if('hidden' != $('#ch_id_produits')[0].type){
			// student_proof
		    if($('#ch_id_produits option:selected').text().search('Student') > -1){
		        $('#label_student_proof').show();
		    }
		    else{
		        $('#label_student_proof').hide();
		        $('#ch_student_proof').val(null);
		    }
		    // member
		    if($('#ch_id_produits option:selected').text().search('non member') == -1){
		        $('#label_member_number').show();
		    }
		    else{
		        $('#label_member_number').hide();
		        $('#ch_member_number').val('');
		    }
	    }
	};
	var initPronouns = function(){
	    if($('#ch_pronouns').val() == 'Other'){
	        $('#label_other_pronouns').show();
	    }
	    else{
	        $('#label_other_pronouns').hide();
	        $('#ch_other_pronouns').val('');
	    }
	};
	var initInvoice_different = function(){
	    if($('#ch_invoice_different')[0].checked){
	        $('label.invoice_different_target').show();
	    }
	    else{
	        $('label.invoice_different_target').hide();
	        $('label.invoice_different_target input[type=text],label.invoice_different_target textarea').val('')
	        $('label.invoice_different_target').find('option:eq(1)').prop('selected', 'selected');
	    }
	};
	var initSpecial_arrangement = function(){
	    if($('#ch_special_arrangement_1')[0].checked){
	        $('label.special_arrangement_target').show();
	    }
	    else{
	        $('label.special_arrangement_target').hide();
	        $('label.special_arrangement_target input[type=text],label.special_arrangement_target textarea').val('')
	    }
	};
	var initChildcare_arrangement = function(){
	    if($('#ch_childcare_arrangement_1')[0].checked){
	        $('label.childcare_arrangement_target').show();
	    }
	    else{
	        $('label.childcare_arrangement_target').hide();
	        $('label.childcare_arrangement_target input[type=text],label.childcare_arrangement_target textarea').val('')
	    }
	};
	var initJobdating = function(){
	    if($('#ch_jobdating_0')[0].checked || (!$('#ch_jobdating_1')[0].checked && !$('#ch_jobdating_2')[0].checked)){
	        $('label.jobdating_target').hide();
	        $('label.jobdating_target input[type=text],label.jobdating_target textarea').val('')
	        $('label.jobdating_target').find('option:eq(0)').prop('selected', 'selected');
	    }
	    else{
	        $('label.jobdating_target').show();
	    }
		
	};
	var initAuthorship = function(){
	    if($('#ch_authorship')[0].checked){
	        $('label.authorship_target').show();
	    }
	    else{
	        $('label.authorship_target').hide();
	        $('label.authorship_target input[type=text],label.authorship_target textarea').val('');
	        $('label.authorship_target input').prop('checked', false);
	        $('label.authorship_target').find('option:eq(0)').prop('selected', 'selected');
	    }
	    initpapers_type(1);
	    initpapers_type(2);
	    initpapers_type(3);
	    initpapers_type(4);
	    initpapers_type(5);
	};
	var initpapers_type = function(number){
	    if($('#ch_papers_'+number+'_type').val() == 'Conference'){
	        $('label.papers_'+number+'_type_target').show();
	    }
	    else{
	        $('label.papers_'+number+'_type_target').hide();
	        $('label.papers_'+number+'_type_target input[type=text],label.papers_'+number+'_type_target textarea').val('')
	        $('label.papers_'+number+'_type_target input').prop('checked', false);
	        $('label.papers_'+number+'_type_target').find('option:eq(0)').prop('selected', 'selected');
	    }
		
	};
	var initInvitation = function(){
	    if($('#ch_invitation')[0].checked){
	        $('label.invitation_target').show();
	    }
	    else{
	        $('label.invitation_target').hide();
	        $('label.invitation_target input[type=text],label.invitation_target textarea').val('')
	        $('label.invitation_target input').prop('checked', false);
	        $('label.invitation_target').find('option:eq(0)').prop('selected', 'selected');
	    }
	};	
	var initDay = function(nDay,moment){
		if('onload' == moment){
			if(0 == $('label.day_target_'+nDay+' input:checked').length){
		        $('#ch_day_'+nDay).prop('checked', false);
			}
		}
	    if($('#ch_day_'+nDay)[0].checked){
	        $('label.day_target_'+nDay).show();
	    }
	    else{
	        $('label.day_target_'+nDay).hide();
	        $('label.day_target_'+nDay+' input').prop('checked', false);
	    }
	};	
	var initDay2 = function(moment){
		// onload ch -> select
		if('onload' == moment){
			var test1 	= $('label.day_target_1 input:checked').length;
			var test2 	= $('label.day_target_2 input:checked').length;
			var select 	= '';
			select 	   += test1 > 0 ? 1 : '';
			select 	   += test2 > 0 ? 2 : '';
			//console.log({
			//	do:'onload',
			//	test1:test1,
			//	test2:test2,
			//	select:select,
			//});
			if(false == $('#label_wandt_days').hasClass('a_verifier')){
				$('#ch_wandt_days option[value="'+select+'"]').prop('selected', true);
			}
		}
		if(1 == $('#ch_wandt_days').val()){
	        $('label.day_target_1').show();
	        $('label.day_target_2').hide();
	        $('label.day_target_2 input').prop('checked', false);
	    }
	    else if(2 == $('#ch_wandt_days').val()){
	        $('label.day_target_1').hide();
	        $('label.day_target_2').show();
	        $('label.day_target_1 input').prop('checked', false);
	    }
	    else if(12 == $('#ch_wandt_days').val()){
	        $('label.day_target_1,label.day_target_2').show();
	    }
	};	
	var initSessionTimeout = function(){  
		$.ajax({ 
			url: 'jx.ping.session-92.php', 
			type: 'get', 
			success: function(response){ 
				console.log(response); 
			} 
		}); 
	} 	
    if(X_ELEM === 82){
		// id_produits 						-> student_proof + member_number
        if($('#ch_id_produits')[0]){			$('#ch_id_produits').on('change',function(){   									initId_produits();  			});  	initId_produits();  }
		// pronouns 						-> other_pronouns
        if($('#ch_pronouns')[0]){				$('#ch_pronouns').on('change',function(){   									initPronouns();  				});     initPronouns();  }
		// invoice_different_trigger 		-> invoice_different_target
        if($('#ch_invoice_different')[0]){		$('#ch_invoice_different').on('change',function(){  							initInvoice_different();  		});     initInvoice_different();  }
		// special_arrangement_trigger		-> special_arrangement_target
        if($('.special_arrangement_trigger input')[0]){	$('.special_arrangement_trigger input').on('change',function(){			initSpecial_arrangement(); 		});     initSpecial_arrangement();  }
		// childcare_arrangement_trigger	-> childcare_arrangement_target
        if($('.childcare_arrangement_trigger input')[0]){	$('.childcare_arrangement_trigger input').on('change',function(){	initChildcare_arrangement(); 	});     initChildcare_arrangement();  }
		// jobdating_trigger 				-> jobdating_target
        if($('.jobdating_trigger input')[0]){	$('.jobdating_trigger input').on('change',function(){							initJobdating(); 				});     initJobdating();  }
		// authorship_trigger				-> authorship_target
        if($('#ch_authorship')[0]){				$('#ch_authorship').on('change',function(){										initAuthorship(); 				});     initAuthorship();  }
		// papers_type_x_trigger 				-> papers_type_target
        if($('#ch_papers_1_type')[0]){			$('#ch_papers_1_type').on('change',function(){									initpapers_type(1); 			});     initpapers_type(1);  }
        if($('#ch_papers_2_type')[0]){			$('#ch_papers_2_type').on('change',function(){									initpapers_type(2); 			});     initpapers_type(2);  }
        if($('#ch_papers_3_type')[0]){			$('#ch_papers_3_type').on('change',function(){									initpapers_type(3); 			});     initpapers_type(3);  }
        if($('#ch_papers_4_type')[0]){			$('#ch_papers_4_type').on('change',function(){									initpapers_type(4); 			});     initpapers_type(4);  }
        if($('#ch_papers_5_type')[0]){			$('#ch_papers_5_type').on('change',function(){									initpapers_type(5); 			});     initpapers_type(5);  }
		// invitation_trigger 				-> invitation_target    	
        if($('#ch_invitation')[0]){				$('#ch_invitation').on('change',function(){										initInvitation(); 				});     initInvitation();  }
		// day_trigger 						-> day_target_1 et day_target_2    	
        //if($('#ch_day_1')[0]){					$('#ch_day_1').on('change',function(){											initDay(1); 					});     initDay(1,'onload');  }
        //if($('#ch_day_2')[0]){					$('#ch_day_2').on('change',function(){											initDay(2);	 					});     initDay(2,'onload');  }
        if($('#ch_wandt_days')[0]){					$('#ch_wandt_days').on('change',function(){									initDay2();	 					});     initDay2('onload');  }
        // previous
        $('#previous1,#previous2').on('click',function(){
		    return confirm("Navigating to the previous page will delete the data already entered");
		})
		// Session time
		setInterval(initSessionTimeout,20*60*1000); 		
    }
    else if(X_ELEM === 83){
    	$('form.form_cb').on('submit',function(e){
			e.preventDefault();
			e.returnValue = false;
			var $form = $(this);
			$.ajax({
				url: 'jx-86-'+jx_inscriptions_id+'.php',
				context: $form,
				success: function(){
					console.log('success');
//	  				return true;
//	  				this.submit();
				},
				error: function(){
					console.log('error');
				},
	            complete: function() { // make sure that you are no longer handling the submit event; clear handler
					console.log('submit');
            		this.off('submit');
	                this.submit();
				}
			});
//			return false;
    	});
    }

    else if(X_ELEM === 78){
		// booth <-> booth_size + competitors
        if($('#ch_booth')[0]){			$('#ch_booth').on('change',function(){   		initBooth();  });        		initBooth();  }
		// sponsorship <-> booth_price
        if($('#ch_sponsorship')[0]){	$('#ch_sponsorship').on('change',function(){   	initSponsorship();  });        	initSponsorship();  }
		// Calcul du total : sponsorship + booth - discount + option_charging_station + option_ad_page
        if($('#fieldset_f6')[0] && $('.trigger_prices')[0]){		$('.trigger_prices').on('change',function(){   	initPrices();  });        	initPrices();  }
    }
	var sw = function(qui) {
		var obj = $(qui)[0];
		if (obj.style.display !== 'none') {
			obj.style.display = 'none';
		}
		else if (obj.style.display !== 'block') {
			obj.style.display = 'block';
		}
		return false;
	};


	// DEBUG
	if ($('#debug1')[0] && $('#debug2')[0]) {
	  sw('#debug1');
	  sw('#debug2');
	  $('#bt_debug1').bind('click', function() {
		sw('#debug1');
		sw('#bt_debug2');
		return false;
	  });
	  $('#bt_debug2').bind('click', function() {
		sw('#debug2');
		sw('#bt_debug1');
		return false;
	  });
	  $('#debug1,#debug2,#bt_debug1,#bt_debug2').css('opacity', '0.9');
	  var debugs = new Array($('div#debug1 li'), $('div#debug2 li'));
	  for (var i = 0; i < debugs.length; i++) {
		var LIs = debugs[i];
		for (var j = 0; j < LIs.length; j++) {
		  var node = LIs[j];
		  if (node.lastChild && node.lastChild.nodeName === 'UL') {
			node.lastChild.style.display = 'none';
			var aEtiquette = node.firstChild;
			var newA = document.createElement('A');
			var newAText = document.createTextNode('[*] ');
			newA.appendChild(newAText);
			newA.setAttribute('href', '#');
			node.insertBefore(newA, aEtiquette);
			newA.onclick = function() {
			  if (this.parentNode.lastChild.style.display !== 'none') {
				this.parentNode.lastChild.style.display = 'none';
			  }
			  else if (this.parentNode.lastChild.style.display !== 'block') {
				this.parentNode.lastChild.style.display = 'block';
			  }
			  return false;
			};
			newA.onfocus = function() {
			  this.blur();
			};
		  }
		}
	  }
		function outOfBoundsElementsFinder() {
		  var all = document.querySelectorAll("*"),
		        i = 0,
		        rect;

		  for (; i < all.length; i++) {
		        rect = all[i].getBoundingClientRect();
		        if (rect.right > document.documentElement.offsetWidth || rect.left < 0) {
		          console.log(all[i]);
		          all[i].style.borderTop = "3px solid red";
		          all[i].style.borderLeft = "1px solid red";
		          all[i].style.borderBottom = "3px solid blue";
		          all[i].style.borderRight = "1px solid blue";
		        }
		  }
		}
	}

});

}(this);
