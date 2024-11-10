
const langStartsWithEn = new RegExp('^en(\-)?','i');
var rwUrlInfo = []

// Ved produksjonssetting: teacher, testing: admin
if (!!ENV.current_user_roles && ENV.current_user_roles.indexOf('teacher') >= 0){

//  T O   K L A S S E R   M Å   F Ø R S T   T A S   M E D :

/**
 * Brukes for å redusere scriptkode når man vil opprette HTML-elementer.
 *
 * @param      {string}   type   En standard HTML-tagg, som p, h1, div, select, option o.s.v..
 * @param      {object}   attrs  HTML-taggens egenskaper (attributter), såsom 'style', 'class' o.s.v..
 *
 * @return     {object}   Et HTML-nodeobjekt (ikke bare ren HTML).
 *
 * @author     Terje Rudi, Rudi Multimedia -20.
 *
 */
class CreateHTMLelement{
    constructor(){
        this.freshNode = false;
    }
    make(type = 'div',attrs = {}){

        try{
            this.freshNode = document.createElement(type);
            for (let k in attrs){
                if (['click','submit','change','focus','blur'].indexOf(k) < 0){

                    // Not an event
                    if (k == 'innerText' || k == 'innerHTML' || k == 'text'){
                        this.freshNode[k] = attrs[k];
                    }else if (k == 'options'){
                        if (type.toLowerCase() == 'select'){
                            let opts = new CreateHTMLelement();
                            for (let o in attrs[k]){
                                this.freshNode.appendChild(opts.make('option',{
                                    'value' : o,
                                    'text' : attrs[k][o]
                                }));
                            }
                        }else{
                            console.error('Element av type ' + type + ' skal ikkje ha option values')
                        }
                    }else{
                        this.freshNode.setAttribute(k,attrs[k]);
                    }
                }else{
                    this.freshNode.addEventListener(k,attrs[k]);
                }
            };
        }catch(e){
            this.freshNode = this.make('p',{
                'class' : 'error',
                'innerHTML' : '<strong>Feil:</strong> Kunne ikkje teikna element ' + type + ': ' + e
            });
        };
        return this.freshNode;
    };
}

/**
 * Oppretter et verktøy for visning av HTML-lementer hvis HTML blir mulig å kopiere over i Sett-inn-felt i Rikt-innhold-editoren i Canvas.
 *
 * @param      {object}		customTools		Et javascriptobjekt med ett eller flere HTML-DOM-objekter som skal være verktøy for HTML-elementproduksjon.
 * @param      {url}		cssHref   		Lenke til et stilark som benyttes i selve editoren, slik at objektene ser like ut som på ferdig side.
 *
 * @author     Terje Rudi, HVL -21.
 *
 */
class CustomEmbedHTMLSuggestionsTool{
    constructor(customTools,cssHref = false){
        // Navn på klassen brukes til feilmelding.
        let nameOfClass = this.constructor.name;
        // Lavere enn body vil ikke fungere, sidan dialogvinduet vil ligge på øverste nivå.
        this.parentNodeToObserve = document.body;
        // Spesifiserer hvilken type endringer i sida som skal reageres på.
        this.obsConfig = { attributes: false, childList: true, subtree: false };
        this.parentNodeObserver = new MutationObserver(function(mutationsList, observer){

            // Sette inn stilark dersom tilgjengelig (url-basert).
            if (cssHref != false){
                try{
                    let customEditorCSSid = 'customEditorCSS';
                    let customEditorStylesheet = document.getElementById(customEditorCSSid);
                    if (!customEditorStylesheet){
                        if (typeof tinymce.activeEditor.iframeElement != 'undefined'){
                        	// Rik-tekst-editor er aktiv, side-administrator er i redigeringsmodus.
                            fetch(cssHref)
                                .then(response => {return response.text()})
                                .then(text => {
                                let doc = tinymce.activeEditor.iframeElement.contentWindow.document;
                                let editorcss = doc.createElement('style');
                                editorcss.id = customEditorCSSid;
                                editorcss.innerText = text;
                                doc.head.appendChild(editorcss);
                            })
                                .catch(e => {console.error('Implemetering stilark for tekst-editor: ' + e)})
                        }
                    }
                }catch(e){
                    // Ignorerer
                }
            }
            // Ferdig med stilark

            // Dialogvinduet har denne data- og role-egenskapen. Viktig med riktig selector, siden mange deler 'data-mce-component'.
            let insertDialogBox = document.querySelector('span[data-mce-component][role="dialog"]');
            if (!!insertDialogBox){
                // Vi er ute etter den vindusdialogen som inneholder et textarea.
                let dialogTextarea = insertDialogBox.querySelector('textarea');
                if (!!dialogTextarea){
                    // Trenger klassen mkNode for å spare koding
                    let elm = new CreateHTMLelement();
                    // Tekst etter språk
                    let labelText = {
                        en : 'Copy HTML from here and paste into the above field:',
                        nn : 'Kopier HTML herifrå og lim inn i øvste feltet:',
                        nb : 'Kopier HTML herfra og lim inn i øverste felt:',
                        no : 'Kopier HTML herifrå og lim inn i øvste feltet:',
                    }

                    // Konstruerer 'kopier fra'-området.
                    let createCopyFrom = function(){
                        // Her opprettes label og textarea til verktøyet som HTML skal kopieres fra,
                        // MERK: Dette elementet blir fjernet, dersom tekstfeltet med kode tømmes. Nødvendig, fordi editor-script manipulerer DOM for oss ved tømming.
                        let obj = elm.make('div',{id:'copyFromDyn'});
                        obj.appendChild(elm.make('label',{class:'control-label',innerText:labelText[document.documentElement.lang]}));
                        obj.appendChild(elm.make('textarea',{
                            id:'suggestedHTML',
                            class: 'tiltIn',
                            style:'transform: rotateX(0deg);background: lightyellow;height: 6rem;width: 97.5%;font-family: monospace;line-height: 1em;',
                            'blur' : function(){if(this.value.length <= 0){this.parentNode.outerHTML = ''};}
                        }));
                        return obj;
                    }
                    let copyFromArea = elm.make('section',{id:'copyFromArea',style:'margin: 1rem 0;font-family: Lato Extended,Arial,sans-serif;'});

                    // Legger ut 'kopier fra'-området.
                    dialogTextarea.parentNode.parentNode.parentNode.appendChild(copyFromArea);

                    // Legger til verktøyene (HTML-elementobjekter) som institusjonen ønsker å tilby (disse er generert utenfor denne klassen, men sendes hit i argumentet customTools).
                    try{
                        for (let k in customTools){
                            // Legger til et klikk-event for hele verktøyet, men klikk-operasjon utføres bare på HTML-elementer som har egenskapen data-offer-html="true".
                            customTools[k].addEventListener('click',function(event){
								try{
									let source = event.target || event.srcElement;
									// Sjekker oppover (bubble up) i hierarkiet for å finne omsluttende tagg/objekt (moderobjekt).
									while (source.dataset['offerHtml'] == undefined){
										source = source.parentNode;
									}

									if (!!source && !!source.dataset['offerHtml']){

										let copyFromTool = document.getElementById('copyFromArea');
										if (!!copyFromTool){
											let putWhatToCopyHere = document.getElementById('suggestedHTML');
											if (!!putWhatToCopyHere){
												/*console.log('putWhatToCopyHere (suggestedHTML) er tilgjenge.')*/;
											}else{
												copyFromTool.appendChild(createCopyFrom());
												putWhatToCopyHere = document.getElementById('suggestedHTML');
											}
											try{
												let tidyUp = new RegExp('(<p>)(\\W*)(<\\/p>)','i'); // Fjerner ein tom <p>-tagg
                                                putWhatToCopyHere.innerText = source.innerHTML.replace(tidyUp,'');
                                                putWhatToCopyHere.parentNode.parentNode.style.display = 'block';
                                                // Blinke-effekt
                                                let cnt = 0;
                                                let blink = setInterval(function () {
                                                    cnt++;
                                                    putWhatToCopyHere.style.visibility = 'hidden';
                                                    setTimeout(function () {
                                                        putWhatToCopyHere.style.visibility = '';
                                                    },40);
                                                    if (cnt >= 2){
                                                        clearInterval(blink);
                                                    }
                                                },80);
                                                // Marker koden som skal kopieres.
                                                putWhatToCopyHere.select();
											}catch(e){
												console.error('Kunne ikkje legge ut html til kopiering: ' + e);
											}
										}else{
											console.error('Element med id copyFromArea er ikkje tilgjenge!');
										}

									}
									event.preventDefault();
								}catch(e){
									// ignorer
								}
                            });
                            dialogTextarea.parentNode.parentNode.parentNode.appendChild(customTools[k]);
                        }
                    }catch(e1){
                        try{
                            dialogTextarea.parentNode.parentNode.parentNode.appendChild(customTools);
                        }catch(e2){
                            console.error('Objekt til klassen ' + nameOfClass + ' har feil: ' + e1 + ' ' + e2);
                        }
                    }
                }
            }
        })
        // Selve obersvasjonen (å vente på at målområdet faktisk er til stede) trigges.
		this.parentNodeObserver.observe(this.parentNodeToObserve, this.obsConfig);
    }
}


// H E R   O P P R E T T E S   V E R K T Ø Y T I L B U D E T   F O R   B R U K E R E N :


// Terjes
class CreateToolForCreatingDetailsHTML{
	constructor(){

        // Produksjon av vertyg
		this.id = 'detailsVerktyg';
		try{
			this.elm = new CreateHTMLelement();
			let infoLink = '<a style="background-image: url(https://v.hvl.no/symbols/info.php.svg);background-size: 1rem;background-repeat: no-repeat;padding-left: 1.4rem;height: 1.2rem;float: right;font-size: .9em;" href="https://www.w3schools.com/tags/tag_details.asp" target="_blank">&lt;details&gt;</a>';
			this.toolLabels = {
				summary : {
					no : 'Detaljar med/utan ikon:' + infoLink,
					en : 'Details with or with&shy;out icon:' + infoLink,
					nn : 'Detaljar med/utan ikon:' + infoLink,
					nb : 'Detaljer med/uten ikon:' + infoLink
				},
				clickMe : {
					no : 'Klikk på dette dømet for å få ut HTML-koden',
					en : 'Click this sample to extract the HTML code',
					nn : 'Klikk på dette dømet for å få ut HTML-koden',
					nb : 'Klikk på dette eksempelet for å få ut HTML-koden'
				},
				makeFrame : {
					no : 'Ramme og kulør:',
					en : 'Frame and color:',
					nn : 'Ramme og kulør:',
					nb : 'Ramme og farge:'
				},
				frameNeutral : {
					no : 'Nøytral utan ramme',
					en : 'Borderless neutral',
					nn : 'Nøytral utan ramme',
					nb : 'Nøytral uten ramme'
				},
				frameTip : {
					no : 'Grønt tips',
					en : 'Green tip',
					nn : 'Grønt tips',
					nb : 'Grønt tips'
				},
				frameWarning : {
					no : 'Raud åtvaring',
					en : 'Red warning',
					nn : 'Raud åtvaring',
					nb : 'Rød advarsel'
				},
				shadow : {
					no : 'Skugge:',
					en : 'Shadow:',
					nn : 'Skugge:',
					nb : 'Skygge:'
				},
				icon : {
					no : 'Ikon:',
					en : 'Icon:',
					nn : 'Ikon:',
					nb : 'ikon:'
				},
				iconNo: {
					no : 'Utan ikon',
					en : 'No icon',
					nn : 'Utan ikon',
					nb : 'Uten ikon'
				},
				iconDialog: {
					no : 'Dialogikon',
					en : 'Dialog icon',
					nn : 'Dialogikon',
					nb : 'Dialogikon:'
				},
				iconInfo: {
					no : 'Info',
					en : 'Info',
					nn : 'Info',
					nb : 'Info:'
				},
				iconLes: {
					no : 'Les',
					en : 'Read',
					nn : 'Les',
					nb : 'Les:'
				},
				iconMal: {
					no : 'Mål',
					en : 'Target',
					nn : 'Mål',
					nb : 'Mål:'
				},
				iconSitat: {
					no : 'Sitat',
					en : 'Quote',
					nn : 'Sitat',
					nb : 'Sitat:'
				},
				iconFrage: {
					no : 'Spørsmål',
					en : 'Question icon',
					nn : 'Spørsmål',
					nb : 'Spørsmål:'
				},
				iconParagraf: {
					no : 'Paragraf',
					en : 'Law',
					nn : 'Paragraf',
					nb : 'Paragraf:'
				},
				iconTid: {
					no : 'Tid',
					en : 'Time',
					nn : 'Tid',
					nb : 'Tid:'
				},
				iconTips: {
					no : 'Tips',
					en : 'Tip',
					nn : 'Tips',
					nb : 'Tips:'
				},
				iconVerktyg: {
					no : 'Verktyg',
					en : 'Tool',
					nn : 'Verktyg',
					nb : 'Verktøy:'
				},
				iconViktig: {
					no : 'Viktig',
					en : 'Important',
					nn : 'Viktig',
					nb : 'Viktig:'
				},
				iconHodeskalle: {
					no : 'Hovudskalle',
					en : 'Scull',
					nn : 'Hovudskalle',
					nb : 'Dødningehode:'
				},
				openClosed: {
					no : 'Open frå start:',
					en : 'Open as default:',
					nn : 'Open frå start:',
					nb : 'Åpen fra start:'
				}
			}
		}catch(e){
			console.error('Problem med opprettelse av verktyg: ' + e);
		}
	}
	makeTool(e) {
		let displayId = 'rammeToolDisplay';
		let pattern = new RegExp("[^a-z0-9]", "i");
		let langArr = document.documentElement.lang.split(pattern);
		let lang = 'nn';
		switch (langArr[0]) {
		  case 'no':
			if (typeof langArr[1] != 'undefined' && langArr.toUpperCase() == 'NB' ){
				lang = 'nb'
			}
			break;
		  case 'en':
		  	lang = 'en';
		  	break;
		  case 'nb':
			lang = 'nb';
			break;
		  default:
			lang = 'nn';
		}
		/*if (lang == 'en-GB'){
			lang = 'en';
		}
		console.info('Språk eller målføre: ' + lang);
		*/
		if (!!this.elm){

			let id = this.id;
			let toolLabels = this.toolLabels;
            let fieldSetStyle = 'margin-top: .4rem;display: flex;flex-wrap: wrap;border: dotted 1px lightgrey;border-radius: .3rem;padding: .1rem .6rem;justify-content: space-between;align-items: center;';
            let toolItemStyle = 'display: flex;flex-wrap: nowrap;align-items: center;padding: 0;';
            let checkBoxStyle = 'margin: 0 0 0 .4rem;';
            let toolItemLabelStyle = 'margin: 0 .2rem 0 0';

			let refreshHTMLexample = function(){
				let tools = document.getElementById(id).querySelectorAll('select, input');
                let openCloseStatus = '';
				let css = [];
				tools.forEach(function(elm){
                    if (!!elm.id && elm.id == 'openAsDefault' && !!elm.checked){
                        openCloseStatus = ' open';
                    }else{
                        if (!!elm.selectedIndex){
                            css.push(elm.options[elm.selectedIndex].value);
                        }else if (!!elm.checked){
                            css.push(elm.value);
                        }
                    }
				});
				let classes = css.join(' ');
				let howTo = toolLabels.clickMe[lang];
				let htmlOut = `<p class="control-label"><small><em>${howTo}:</em></small></p>
<div style="cursor: pointer;" data-offer-html="true" title="${howTo}">
  <details${openCloseStatus} class="boks ${classes}">
    <summary>Lorem Ipsum</summary>
     <p>Lorem ipsum dolor sit amet, consectetuer adipiscing elit. Curabitur vitae massa.
     Etiam at purus. Vestibulum tortor libero, viverra quis, mattis vitae, pulvinar eu, massa.
     Donec nulla tellus, congue id, commodo id, tincidunt eu, metus.<p>
  </details>
</div>`;
				document.getElementById(displayId).innerHTML = htmlOut;
			}

			let wrap = this.elm.make('details',{id : this.id,style:'font-family: Lato Extended,Arial,sans-serif;'});
            wrap.appendChild(this.elm.make('summary',{
				innerHTML : this.toolLabels.summary[lang],
                style : 'cursor: pointer;'
			}));
			let fldSet = this.elm.make('fieldset',{
				style : fieldSetStyle
			});

			// Ramme-valg.
			let border = this.elm.make('p',{style:toolItemStyle});
			border.appendChild(this.elm.make('label',{
				innerText : this.toolLabels.makeFrame[lang],
                style : toolItemLabelStyle
			}));
			let borderSelect = this.elm.make('select',{
				id : 'rammeType',
				style : 'margin: 0;',
				name : 'rammeType',
				options : {
					' ' : this.toolLabels.frameNeutral[lang],
					tips : this.toolLabels.frameTip[lang],
					varsel : this.toolLabels.frameWarning[lang],
				}
			});
			borderSelect.addEventListener('change',function(){refreshHTMLexample()});
			border.appendChild(borderSelect);
			fldSet.appendChild(border);


			// Skygge-valg.
			let shadow = this.elm.make('p',{style:toolItemStyle + 'border: solid 1px lightgrey;padding: 0 .4rem;border-radius: .2rem;min-height: 2.2rem;'})
			shadow.appendChild(this.elm.make('label',{
				innerText : this.toolLabels.shadow[lang],
                style : toolItemLabelStyle
			}));
			let shadowCheckbox = this.elm.make('input',{
				id : 'rammeSkygge',
				name : 'rammeSkygge',
				style : checkBoxStyle,
				type : 'checkbox',
				value : 'skugge'
			});
			shadowCheckbox.addEventListener('change',function(){refreshHTMLexample()});
			shadow.appendChild(shadowCheckbox);

			fldSet.appendChild(shadow);

			// Ikon-valg.
			let icon = this.elm.make('p',{style:toolItemStyle});
			icon.appendChild(this.elm.make('label',{
				innerText : this.toolLabels.icon[lang],
                style : toolItemLabelStyle
			}));
			let iconSelect = this.elm.make('select',{
				id : 'rammeType',
				style : 'margin: 0;',
				name : 'rammeType',
				options : {
					' ' : this.toolLabels.iconNo[lang],
					'ikon ikonDialog' : this.toolLabels.iconDialog[lang],
					'ikon ikonInfo' : this.toolLabels.iconInfo[lang],
					'ikon ikonLes' : this.toolLabels.iconLes[lang],
					'ikon ikonMaal' : this.toolLabels.iconMal[lang],
					'ikon ikonSitat' : this.toolLabels.iconSitat[lang],
					'ikon ikonSpoersmaal' : this.toolLabels.iconFrage[lang],
					'ikon ikonParagraf' : this.toolLabels.iconParagraf[lang],
					'ikon ikonTid' : this.toolLabels.iconTid[lang],
					'ikon ikonTips' : this.toolLabels.iconTips[lang],
					'ikon ikonVerktoey' : this.toolLabels.iconVerktyg[lang],
					'ikon ikonViktig' : this.toolLabels.iconViktig[lang],
                    'ikon ikonHodeskalle' : this.toolLabels.iconHodeskalle[lang]
				}
			});
			iconSelect.addEventListener('change',function(){refreshHTMLexample()});
			icon.appendChild(iconSelect);
			fldSet.appendChild(icon);

            /* NB! Canvas sin editor fjerner open-egenskapen!
            // Open or closed
			let openOrClosed = this.elm.make('p',{style:toolItemStyle})
			openOrClosed.appendChild(this.elm.make('label',{
				innerText : this.toolLabels.openClosed[lang],
                style : toolItemLabelStyle
			}));
			let openOrClosedCheckbox = this.elm.make('input',{
				id : 'openAsDefault',
				name : 'openAsDefault',
				style : checkBoxStyle,
				type : 'checkbox',
				value : 'open',
                checked : 'true'
			});
			openOrClosedCheckbox.addEventListener('change',function(){refreshHTMLexample()});
			openOrClosed.appendChild(openOrClosedCheckbox);

			fldSet.appendChild(openOrClosed);
            */

			wrap.appendChild(fldSet);

			wrap.appendChild(this.elm.make('div',{id:displayId}));

			return wrap;
		}
		return false;
	}
}

// M A G I E N

// Lage detaljverktøyet
let details = new CreateToolForCreatingDetailsHTML().makeTool(this);
// Sørge for at verktøyet blir vist og tilgjengelig for brukerne
let CEHST = new CustomEmbedHTMLSuggestionsTool({details},'https://v.hvl.no/canvas/css/rammebokser-med-ikoner.css.php');




} // Se øverste linje!

console.info('mime.js fra d.hvl.no implementert.');

// MIME CHATROBOT (v/Ivar Rosenberg)


async function implementMimeChatJS(){
	//Denne delen av scriptet ser etter enkelte url'er som vi ønsker skal gi mimehjelp og setter actionId som brukes av boost init for å definere startTriggerActionId.
	//Det settes også bildeurl for knappen slik at lyspære vises der Mime kan hjelpe med f.eks speedgrader.
	var actionId=3002;
	var buttonUrl = "https://www.hvl.no/globalassets/hvl-internett/dokument/mime_human_chat_canvas.png";

	if (window.location.href.indexOf("gradebook/speed_grader") > -1){
		actionId = 2155;
		buttonUrl = "https://www.hvl.no/globalassets/hvl-internett/dokument/mime_human_chat_canvas.png";
		console.info("Speedgrader er identifisert og knappen vises. hva er så actioniden: " + actionId);
	}
	
	else if (window.location.href.indexOf("https://hvl.instructure.com/login/canvas") > -1){
		actionId = 2405;
		buttonUrl = "https://www.hvl.no/globalassets/hvl-internett/dokument/mime_human_chat_canvas.png";
	}		
	else {
		actionId = 3002;
		buttonUrl = "https://www.hvl.no/globalassets/hvl-internett/dokument/mime_human_chat.png";
	}
	
	let s = document.createElement('script');
	s.src = 'https://hvl.boost.ai/chatPanel/chatPanel.js';
	document.head.appendChild(s);
	
	
	var conversationIdKeyName = "hvlConversationId";
	var conversationStartedTimestampKeyName = 'hvlConversationTimestamp';
	var dayInMilliseconds = 7200000;
		
	setTimeout(function(){
		try{	
			let options =  {
				chatPanel: {
					header: {
						filters: { filterValues: "Canvas" },
						title: "Chat"
					},
					settings: {
						conversationId: sessionStorage.getItem(conversationIdKeyName),
						//setter startaction basert på om URL treffer mot if-sjekken helt øverst i scriptet
						startTriggerActionId: actionId
					}
				}
			};
			
			let boost = window.boostInit("hvl", options);

			function add_boostbutton() { 			
			//Mime gir canvashjelp basert på hvor brukeren er
			
			/*
				let boostbutton = document.createElement('boost-trigger-button:button'); 
				boostbutton.id ='boost-trigger-button';
				boostbutton.className = 'boost-trigger-button';
				boostbutton.onclick = function(ev) {boost.chatPanel.show();}
				boostbutton.innerHTML = "Chat med Mime";
			*/
			//de 5 linjene over kan nok fjernes
				let boostBtnId = 'boostbuttondiv';
				let boostbuttondiv = document.createElement('div');
				boostbuttondiv.id = boostBtnId;
				boostbuttondiv.style.width = '7rem';
				boostbuttondiv.style.display = 'flex';
				boostbuttondiv.style.gap = '.4rem';
				boostbuttondiv.style.alignItems = 'center';
				
				/**/ // Navn
				let n = document.createElement('p');
				n.innerText = 'MIME';
				n.style.textShadow = 'rgba(255,255,255,1) 0 0 4px';
				boostbuttondiv.appendChild(n);
				
				// Ikon
				let imgtag = document.createElement('img');
				imgtag.src = buttonUrl;
				imgtag.width ='75';
				imgtag.height ='86';
				imgtag.alt="Mime sin avatar";
				imgtag.onclick = function(ev) {					
					ShowChat();
				}
				boostbuttondiv.appendChild(imgtag);
				
				// Lukke mime-symbolet for de som ønsker det:
				let shut = document.createElement('span');
				shut.style.fontFamily = 'Arial,sans-serif';
				shut.innerHTML = '&times;';
				shut.style.position = 'absolute';
				shut.style.right = '.3rem';
				shut.style.top = '0';
				shut.style.cursor = 'pointer';
				shut.addEventListener('click',() => {	
					document.getElementById(boostBtnId).outerHTML = '';
				});
				boostbuttondiv.appendChild(shut);
				
				document.body.appendChild(boostbuttondiv);
			} //fjernet semikolon 25.10 kl 2345 - Helsinkitid
			
			
		// Lager en margin under navigasjonsknappene module_navigation_target, slik at ikke chatknappen kommer oppÃ¥.
		try{
			document.getElementById('not_right_side').style.marginBottom = '4rem';
		}
		catch(e){
			console.info('implementMimeChatJS: Kunne ikke lage rom under navigasjonsknappene i module_navigation_target: ' + e);
		}

			// Lager en margin under edit_discussion_form_buttons, slik at ikke chatknappen kommer oppÃ¥.
		try{
			document.querySelector('#edit_discussion_form_buttons').style.marginBottom = '4rem';
		}
		catch(e){
			console.info('implementMimeChatJS: Kunne ikke lage rom under navigasjonsknappene i edit_discussion_form_buttons: ' + e);
		}

				// Lager en margin under page-edit_action_buttons slik at chatknappen ikke kommer over knappene når man editerer en side.
		try{
			document.querySelector('.page-edit__action_buttons').style.marginBottom = '4rem';
		}
		catch(e){
			console.info('implementMimeChatJS: Kunne ikke lage rom under page-edit_action_buttons: ' + e);
		}
		



				// Lager en margin under assignment__action-buttons, slik at ikke chatknappen kommer oppÃ¥.
		try{
			document.querySelector('#assignment__action-buttons').style.marginBottom = '4rem';
		}
		catch(e){
			console.info('implementMimeChatJS: Kunne ikke lage rom under navigasjonsknappene i assignment__action-buttons: ' + e);
		}
		
				// Lager en margin under assignment__action-buttons, slik at ikke chatknappen kommer oppÃ¥.
		try{
			document.querySelector('#assignment__action-buttons').style.marginBottom = '4rem';
		}
		catch(e){
			console.info('implementMimeChatJS: Kunne ikke lage rom under navigasjonsknappene i assignment__action-buttons: ' + e);
		}
		
		
			add_boostbutton();
			
			function ShowChat() { 
				boost.chatPanel.show(); 
			}
			if (window.location.href.indexOf("?code=") > -1) {
				ShowChat();
			}
			
			var conversationStartedTimestamp = sessionStorage.getItem(
            conversationStartedTimestampKeyName
        );

        if (conversationStartedTimestamp && Date.now() - Number(conversationStartedTimestamp) >= dayInMilliseconds) {
            sessionStorage.removeItem(conversationIdKeyName);
            sessionStorage.removeItem(conversationStartedTimestampKeyName);
        }

		boost.chatPanel.addEventListener('conversationIdChanged', function (event) {
            sessionStorage.setItem(conversationIdKeyName, event.detail.conversationId);
            sessionStorage.setItem(conversationStartedTimestampKeyName, Date.now());
        });	
	
		}catch(e){
			console.warn('Implementering av Mime chat-robot: ' + e + ' Type: ' + typeof chatPanel)
		}
	},1000);
}
implementMimeChatJS()
	.then(console.info('Chat-robot Mime implementert'))
	.catch((e) => console.error('Feil ved implementering av chat-robot Mime: ' + e))
console.info('Ally er implementert (snart for alle kontoar)');

window.ALLY_CFG = {
    'baseUrl': 'https://prod-eu-central-1.ally.ac',
    'clientId': 898,
    'lti13Id': '107470000000000116'
};
$.getScript(ALLY_CFG.baseUrl + '/integration/canvas/ally.js');













/*
	Licence.	GNU.
	Authors		Tore Bredeli Jørgensen, UiO (uio.no) og Terje Rudi, Høgskulen på Vestlandet (hvl.no).
*/


/**
 * Setter inn et burger-ikon for snarveimeny til moduler oppe til høyre på alle innholdssider.
 * Bruker funksjoner fra UiO (Tore Bredeli Jørgensen og fleire) for innhenting av modul-navn og 
 * -lenker. Utvidede funksjoner av HVL (Terje Rudi) til å plassere et meny-ikon inn i modulsidene.
 * Rekkefølge i menyen er lik rekkefølge i modulen.
 */
 
let localModuleMenuSet = false;

try{

    'use strict';

	if (!ENV.COURSE_ID) ENV.COURSE_ID = /\/courses\/(\d+)/.exec(location.pathname)[1];
	let uio_entireCourseStructure = {modules:{}};
	uio_entireCourseStructure.done = function(){
		return this.requests && this.answers == this.requests
	};

	/* med JSONP:
	async function uioCanvasAPIGet (last_part_of_url){
		//Does a Canvas API call and returns a JS-object.
		let response = await fetch(location.origin+'/api/v1/'+last_part_of_url+'?per_page=1000');
		let data = await response.text();
		return JSON.parse(data.substring(9));
	}
	*/
	async function uioCanvasAPIGet (last_part_of_url){
		// DEV: console.info('HVL: MODULMENY uioCanvasAPIGet aktivert');
	   //Does a Canvas API call and returns a JS-object.
	   let response = await fetch(location.origin+'/api/v1/'+last_part_of_url+'?per_page=1000');
	   return await response.json();
	}

	async function uioGetEntireStructure (){
		// DEV: console.info('HVL: MODULMENY uioGetEntireStructure aktivert');
		//load all modules and items, and insert all items into one array for each module -- uio_entireCourseStructure.modules[moduleid] = items json-data.
		let modules = await uioCanvasAPIGet (`courses/${ENV.COURSE_ID}/modules`);
		for (let module of modules){
			let items = await uioCanvasAPIGet (`courses/${ENV.COURSE_ID}/modules/${module.id}/items`);
			module.itemlist = items;
		}
		return modules;
	}

	function uioGetCurrentModuleItemId(modules) {
		// DEV: console.info('HVL: MODULMENY uioGetCurrentModuleItemId aktivert');
		
		if (location.search.indexOf('menu=off')!=-1) return false;
		
		let match = /courses\/\d+\/modules\/items\/(\d+)$/.exec(location.pathname);
		
		if (match) return match[1];
		match = /module_item_id=(\d+)/.exec(location.search);
		
		if (match){
			
			//a link from another page can have wrong module_item_id and still show the correct page, so check that the id is valid
			if (uioGetCurrentModuleIndex(match[1],modules)) return match[1];
		}
		//lacking module_item_id or has erroneous module_item_id
		for (let module of modules) {
			for (let item of module.itemlist){
				let itemurl = /\/courses.*/.exec(item.url);
				if (itemurl && itemurl[0] == location.pathname){
					return item.id;
				}
			}
		}
	}

	function uioGetCurrentModuleIndex(moduleItemId,modules){
		// DEV: console.info('HVL: MODULMENY uioGetCurrentModuleIndex aktivert');
		for (let i=0; i<modules.length; i++) {
			for (let item of modules[i].itemlist) {
				if (item.id == moduleItemId) return i;
			}
		}
		return false;
	}

	function uioMakemenu(modules, moduleIndex, itemId){
		// DEV: console.info('HVL: MODULMENY uioMakemenu aktivert');
		//insert css
		let style = document.createElement('style');
		style.type = 'text/css'
		style.innerText = `
		   ul.uioMeny {
			  list-style-type: none;
			  background-color: WhiteSmoke; /* #fafafa; */
			  font-size: 95%;
			  border: 1px solid Gainsboro; /* #d1d1d1; */
			  border-radius: 3px;
		   }
		   .uioMeny li a {
			  display: block;
			  color: Black; /* #555; */
			  padding: 3px 0 3px 16px !important;
			  text-decoration: none;
		   }
		   .uioMeny li a:hover {
			  background-color: #ddd;
			  color: Black;
		   }
		   .uioMeny li {
			  padding: 0px !important;
			  text-align: left;
			  border-bottom: 1px solid White;
		   }
		   .uioMeny li:last-child {
			  border-bottom: none;
		   }
		   .uioMeny .module-home a{
			  font-weight: bold;
			  font-size: 100%;
			  background-color: #007bc4; /* #58b2dc; */
			  color: White !important;
		   }
		  .uioMeny .module-home a:hover {
			 background-color: Gray; /* #7c7474 !important; */
		  }
		  .uioMeny li.uioMeny-aktiv {
			 padding: 3px 0px 3px 16px !important;
			 background-color: Gray; /* #787676 !important; */
			 color: White !important;
		  }
		`;
		document.head.appendChild(style);
		//make menu
		let items = modules[moduleIndex].itemlist;
		let menuhtml = '<ul class="uioMeny">';
		menuhtml += `<li class="module-home"><a href="/courses/${ENV.COURSE_ID}"><span class="language" lang="no">Tilbake til moduloversikten</span><span class="language" lang="en">Back to the module list</span></a></li>`;
		for (let i=0; i<items.length; i++){
			let newtitle = '';
			let titlearray = items[i].title.split("|");
			for (let i=0; i<titlearray.length; i++){
				let match = /^(\w\w):(.*)/.exec(titlearray[i]);
				if (match){
					newtitle += `<span class="language" lang="${match[1]}">${match[2]}</span>`;
				}else{
					newtitle += titlearray[i];
				}
			}
			menuhtml += '<li';
			if (items[i].completion_requirement && items[i].completion_requirement.completed){
				menuhtml += ' style="background:#aaffaa;"';
			}
			if (items[i].id == itemId){//this is the current item
				menuhtml += ` class="uioMeny-aktiv">${newtitle}</li>`;
			}else{
				menuhtml += `><a href="${items[i].html_url}">${newtitle}</a></li>`;
			}
		}
		menuhtml += '</ul>';
		//insert menu
		$("#right-side").prepend(menuhtml);
		$("#right-side-wrapper").show();
		$("#right-side").show();
	}

	async function uioRightmenu(){
		// DEV: console.info('HVL: MODULMENY uioRightmenu aktivert');
		let modules = await uioGetEntireStructure();
		let itemId = uioGetCurrentModuleItemId(modules);
		if (itemId){
			let moduleIndex = uioGetCurrentModuleIndex(itemId,modules);
			uioMakemenu(modules,moduleIndex,itemId);
		}
	}


	// START HVL-TILLEGG

	/**
	 * Receives rendered HTML for list items in a unordered list, generates a Canvas-look-alike menu button for the menu an places it visually in one of three places on the page.
	 *
	 * @param	{string}	menuElementsHtml	Canvas expects normal 'li' elements with an 'a' element inside.
	 * @param	{string}	destination			Can be one of 'header', 'body' or 'aside'.
	 *
	 * Author	Terje Rudi, HVL
	 *
	 */
	function makeLocalModuleMenu(menuElementsHtml,destination = 'header'){
		// Sterkt avhengig av Canvas sine stiler
		
		// DEV: console.info('HVL: MODULMENY makeLocalModuleMenu aktivert');
	
		// MENYKNAPP
		let navBtn = document.createElement('div');
		navBtn.id = 'localModuleMenu';
		navBtn.style.marginRight = '.23rem';
		navBtn.className = 'inline-block';
		navBtn.role = 'application';
	
		// Lenke inni menyknapp
		let navBtnA = document.createElement('a');
		navBtnA.className = 'btn al-trigger ui-state-active';
		navBtnA.tabindex = '-1';
		navBtnA.role = 'button';
		navBtnA.href = '#';
	
		// Ikonvisning
		let navBtnAI = document.createElement('i');
		navBtnAI.className = 'icon-hamburger';
		navBtnA.appendChild(navBtnAI);
	
		// Span i menyknapp
		let navBtnASpan = document.createElement('span');
		if (window.matchMedia('(max-width: 500px)').matches){
			navBtnASpan.className = 'screenreader-only';
		}else{
			navBtnASpan.style.paddingLeft = '.3em';
			navBtnASpan.innerText = (langStartsWithEn.test(document.documentElement.lang) ? 'Module Menu' : 'Modulmeny');
		}
		navBtnA.appendChild(navBtnASpan);
	
		// Ferdiggjort menyknapp
		navBtn.appendChild(navBtnA);
	
		// Modulelementliste
		let navMenu = document.createElement('ul');
		// Stilklasser fra Canvas som gir menyfunksjonalitet
		navMenu.className = 'al-options ui-menu ui-widget ui-widget-content ui-corner-all ui-popup ui-kyle-menu use-css-transitions-for-show-hide ui-state-open';
		navMenu.style.display = 'none';
		if (destination == 'header'){
			navMenu.style.marginRight = '.4rem';
		}else if (destination == 'body'){
			navMenu.style.marginRight = '1.8rem';
		}
		navMenu.role = 'menu';
		navMenu.tabindex = '0';
	
		// li-elementene
		navMenu.innerHTML = menuElementsHtml;
		// Menyen legges inn i menyknappen
		navBtn.appendChild(navMenu);
			
		try{
			// Sette inn menyknappen på ønsket sted
			if (destination == 'header'){
						
				try {
					let buttons = document.querySelector('.buttons');
					buttons.appendChild(navBtn);
					
					/*
					// .al-trigger er tilgjengeleg berre for tilsette
					// let alTrigger = document.querySelector('.al-trigger');
					alTrigger = null;
					if (alTrigger != null){
						// console.info('HVL: MODULMENY alTrigger = TRUE, alTrigger.parentNode = ' + alTrigger.parentNode.outerHTML);
						// alTrigger.parentNode.style.background = 'yellow';
						alTrigger.parentNode.appendChild(navBtn);
					}else{
						console.info('HVL: MODULMENY alTrigger = NULL');
						
						// Finn plassering av menyknapp for studentbrukerar
						console.info('HVL: MODULMENY student content-wrapper');
						let studentTrgt = document.querySelector('#content-wrapper').querySelector('div,h1,h2,h3,h4,h5,p').querySelector('div,h1,h2,h3,h4,h5,p');
						try {
							navBtn.style.float = 'right';
							navBtn.style.marginTop = '.13rem';
							navBtn.style.marginLeft = '.23rem';
							studentTrgt.insertBefore(navBtn,studentTrgt.children[0]);
							//studentTrgt.appendChild(navBtn);
							// DEV: console.info('HVL: Fann studentTrgt!');
						}catch(e){
							console.error('HVL: Fann ikkje leveringsområde for modul-lokalmeny for studentar: ' + e);
						}
						let buttons = document.querySelector('.buttons');
						
					}
					*/
				}catch(e){
					console.error('HVL: Element med klassen .al-trigger for modulmeny ikkje tilgjenge: ' + e);
				}
			}else if (destination == 'body'){
				// DEV: console.info('HVL: MODULMENY body ');
				navBtn.style.float = 'right';
				let finalTarget = document.querySelector('.show-content');
				finalTarget.insertBefore(navBtn,finalTarget.firstChild);
			}else if (destination == 'aside'){
				// DEV: console.info('HVL: MODULMENY aside');
				let finalTarget = document.querySelector('#right-side');
				finalTarget.insertBefore(navBtn,finalTarget.firstChild);
				document.querySelector('#right-side-wrapper').style.display = 'block';
				document.querySelector("#right-side").style.display = 'block';
			}else{
				console.error('HVL: Ukjend destinasjon ' + destination + ' for for plassering av menyknapp');
			}
		}catch(e){
			console.error('HVL: Kunne ikkje plassere meny ' + e + ' til destinasjon ' + destination);
		}
	}

	/**
	 *
	 * Description	Draws html li elements from an index of module elements in a course. Intendend for use in a local menu on a Canvas page.
	 * Dependencies	The so called 'høyremeny-scriptet' from the University of Osdlo
	 * Based on		Function uioMakemenu made by the University of Oslo
	 *
	 * @param	{json}		modules		From function uioCanvasAPIGet()
	 * @param	{int|false}	moduleIndex	From function uioGetCurrentModuleIndex()
	 * @param	{string}	itemId		From function uioGetCurrentModuleItemId()
	 *
	 * Author	Terje Rudi, HVL
	 *
	 */
	function lokalModulMenyItems(modules, moduleIndex, itemId){
	
		// Menyelmentene (<li>) tegnes som HTML (returneres ikke som objekter)
	
		let listItems = [];
	
		let docLang = '<span lang="en">Module Index</span>';
		if (document.documentElement.lang == 'nn' || document.documentElement.lang == 'nb'){
			docLang = '<span lang="no">Moduloversikt</span>';
		}
		listItems.push(`<li><a href="#" onclick="document.location = 'https://${window.location.host}/courses/${ENV.COURSE_ID}/modules'" style="background: #666;color: white;text-decoration: none;" ><big>&lsaquo;</big> ${docLang}</a></li>`);

		let items = modules[moduleIndex].itemlist;
		try{
			for (let i=0; i<items.length; i++){
				let newtitle = '';
				let titlearray = items[i].title.split("|");
				for (let i=0; i<titlearray.length; i++){
					let match = /^(\w\w):(.*)/.exec(titlearray[i]);
					if (match){
						newtitle += `<span lang="${match[1]}">${match[2]}</span>`;
					}else{
						newtitle += titlearray[i];
					}
				}
				let menuLi = document.createElement('li');
				if (items[i].completion_requirement && items[i].completion_requirement.completed){
					menuLi.style.background = '#aaffaa';
				}
				menuLi.style.borderTop = 'dotted 1px lightgrey';
				let a = document.createElement('a');
				a.style.textDecoration = 'none';
				if (items[i].id == itemId){
					// Nåværende side
					a.href = '#';
					a.style.background = '#666';
					a.style.color = 'white';
					a.style.cursor = 'none';
					a.innerHTML = `<big>&lsaquo;</big> ${newtitle}`;
					menuLi.appendChild(a);
				}else{
					if (items[i].html_url == undefined){
						// Modul-navn definert som kun inndelings/overskrift
						a.href = '#';
						a.style.color = '#004357';
						a.style.fontSize = 'smaller';
						a.style.fontWeight = '100';
						a.style.letterSpacing = '.1em';
						a.innerHTML = `${newtitle}:`;
					}else{
						a.href = items[i].html_url;
						a.innerHTML = `<big>&rsaquo;</big> ${newtitle}`;
					}
					
					menuLi.appendChild(a);
				}

				listItems.push(menuLi.outerHTML);
			}
		}catch(e){
			console.error('Kunne ikke opprette menyelement: ' + e);
		}
		return listItems.join('');
	}

	async function hvlLokalModulMeny(){
		// Denne funksjonen aktiveres med mutationObserver (se: notRightSideObserver).
	
		
		
		try{
			let modules = await uioGetEntireStructure();
			let itemId = uioGetCurrentModuleItemId(modules);
			
			if (itemId){

				let moduleIndex = uioGetCurrentModuleIndex(itemId,modules);
				let moduleIndexHTML = lokalModulMenyItems(modules,moduleIndex,itemId);
				makeLocalModuleMenu(moduleIndexHTML);
				// Spesial for sjukepleie v/Ingrid Heiberg, emne 17771
				if (!!ENV.COURSE_ID && (ENV.COURSE_ID == '17771')){
					//// DEV: console.info('Integerert modulmeny HTML: ' + moduleIndexHTML);
					try{
						let prnt = document.getElementById('wiki_page_show');
						if (!!prnt){
							let trgt = prnt.querySelector('.show-content');
							if (!!trgt){
								let menuStyle = `<style>
									#innlemmetModulMeny {
										float: right;
										max-width: 20rem;
										list-style-type: none;
										border: solid 1px #c8cdd1;
										border-radius: .2rem;
										padding: .8rem;
										padding-bottom: 1.2rem;
										margin: 1rem;
										margin-right: 0;
									}
									#innlemmetModulMeny a {
										text-decoration: none !important;
										background: white !important;
										color: black !important;
									}
								
									</style>`;
								trgt.insertAdjacentHTML('afterbegin', menuStyle + '<ul id="innlemmetModulMeny" style="">' + moduleIndexHTML + '</ul>');
							}
						}
					}catch(e){
						console.error('HVL: Fann ikkje .show-content å levere meny til');
					}
				}
			}else{
				console.error('HVL: itemId = ' + typeof itemId);
			}
		}catch(e){
			console.info('HVL: Integerert lokalmeny skal ikke aktiveres');
		}

	}

	let notRightSideObserver = new MutationObserver(function(){
		if (localModuleMenuSet === false){
			let stickyToolBar = document.querySelector('.sticky-toolbar');
			
			// DEV: console.info('HVL: current_user_id: ' + ENV.current_user_id + ' innerHTML = ' + stickyToolBar.innerHTML);
			
			if (typeof stickyToolBar == 'object' && !!stickyToolBar){
				// DEV: console.info('HVL: A: stickyToolBar er av type: ' + JSON.stringify(stickyToolBar));
				hvlLokalModulMeny();
				localModuleMenuSet = true;
			}else{
				// DEV: console.info('HVL: B: stickyToolBar er av type: ' + JSON.stringify(stickyToolBar));
			}
		}
	});
	notRightSideObserver.observe(document.querySelector('#not_right_side'), {
		childList: true, // observe direct children
		subtree: true, // and lower descendants too
		characterDataOldValue: true // pass old data to callback
	});

// END HVL

}catch(e){
	console.error('ModuleMenuError: ' + e);
}
			
/* if (!!ENV.current_user.id && ENV.current_user.id == '4002'){ // Bolla Pinnsvin */

	// I N I T
	
	const ENDPOINT_CHECK_IF_OPEN_FOR_EVAL = 'https://v.hvl.no/js/skjema/canvas-emneevalueringer/getQuestions.php.js?open=1&task=fsrelation&courseid=';
	const ENDPOINT_CHECK_IF_USER_HAS_EVAL = 'https://v.hvl.no/js/skjema/canvas-emneevalueringer/check-user-status.php.js';
	const EVALSKJEMA_CONTAINER_ID = 'evaluationSection';
	
	const REGEX_IS_ON_COURSE_PAGE = new RegExp('\/courses\/[0-9]*(\/?)','i');
	const REGEX_IS_ON_COURSE_ASSIGNMENTS_PAGE = new RegExp('\/courses\/[0-9]*\/assignments(\/?)','i');
	const REGEX_CHECK_SUBJECT_CODE = new RegExp('^[0-9]*$','g');

	
	
	// F U N C T I O N S

	/**
	 * Fanger aktuelt område i Canvas sin sidestruktur for å legge inn et element som
	 * skal omslutte evalueringsskjema (EVALSKJEMA_CONTAINER_ID). Det skal allerede være
	 * sjekket om emnet er åpent for evaluering.
	 *
	 * @note	Selve 'skjemaløsningen' tar seg av autentisering.
	 */
	function implementEmneEvalSkjema(){
	
		try{
			// Hente inn hovedområde i Canvas-DOM for evalueringsskjema.
			
			let trgtContainer = document.getElementById('ag-list').querySelector('ul');
			if (trgtContainer !== null){
			
				// Oppretter området skjema skal legges inn i.
				var li = document.createElement('li');
				li.id = EVALSKJEMA_CONTAINER_ID;
				li.className = 'item-group-condensed';
				let evalBoxAria = (langStartsWithEn.test(document.documentElement.lang) ? 'Evaluation show/hide evaluation' : 'Evaluering vis/skjul evaluering');
				let evalBoxTitle = 'EVA'; //(langStartsWithEn.test(document.documentElement.lang) ? 'Course Evaluation' : 'Emne-evaluering');
				li.innerHTML = `
					<style>
						summary {
							list-style: none;
						}
						summary::-webkit-details-marker {
							display: none;
						}
						.hvlCustom {
							background-image: url(https://v.hvl.no/grafikk/svg/veiarbeid.svg);
							background-repeat: no-repeat;
							background-position: .9rem 50%;
							background-size: 1.8rem;
						}
					</style>
					<details class="assignment_group" open><summary class="ig-header" style="cursor: pointer;"><h4 class="ig-header-title" style="margin-left: .5rem;">${evalBoxTitle}</h4></summary>
							<div id="skjema" class="ig-row">
								<p><em>Henter data ...</em></p>
							</div>
					</details>
				  `;
				trgtContainer.insertAdjacentElement('afterbegin', li);

				if (document.getElementById('evalScriptId') === null){
				
					// setter inn scriptet som trigger skjemaløsningen.
					
					console.info('HVL: Aktiverer emneevaluering');
					let emneEval = document.createElement('script');
					emneEval.id = 'evalScriptId';
					
					// JWT.
					
					let params = new URLSearchParams(window.location.search);
					
					// Sjekk om pålogget
					let affiliation = params.get('affiliation'); // JWT
					let paramToAdd = '';
					if (affiliation !== null && affiliation.length > 10){
						paramToAdd = '&affiliation=' + affiliation;
					}
					
					emneEval.src = `https://v.hvl.no/js/skjema/index.php?useform=canvas-emneevalueringer${paramToAdd}`;
					emneEval.crossOrigin = 'use-credentials';
					
					document.body.appendChild(emneEval);
		
				}
				
			}
			
		
		}catch(e){
			console.error(`HVL: Implementering skjema: ` + e + `, typeof  document.getElementById('ag-list').querySelector('ul') = ` + typeof document.getElementById('ag-list').querySelector('ul'));
		}
	}
	
	/**
	 * @param	{string}	href	A link representing an URL to a subject.
	 * @param	{bool|obj}	obj		False if type is a course page. Object if it is a
	 * 								link on i.e. the Dashboard.
	 */ 
	function markSubjectForEvaluation(href,obj = false){
	
		const SPLIT = href.split('/');
		if (!!SPLIT[4] && SPLIT[4].match(REGEX_CHECK_SUBJECT_CODE)){
			// Is a subject code, fetching the sis-id.
			console.info('HVL: Sjekker om ' + SPLIT[4] + ' er åpent for evaluering.');
			
			// TODO: Sjekk om det finnes sessionStorage allerede. Hvis ikke:
			
			fetch(ENDPOINT_CHECK_IF_OPEN_FOR_EVAL + SPLIT[4])
			.then(r => r.json())
			.then(json => {
				
				if (!!json.success){
					console.info('HVL: Respons 200:OK for sjekk om ' + SPLIT[4] + ' er åpent for evaluering.');
					if (!!json.data.open && json.data.open == true){
						console.info('HVL: Er åpent. Sjekker om bruker ' + ENV.current_user.id + ' allerede har evaluert ' + SPLIT[4]);
						
						// Check user-status
						fetch(ENDPOINT_CHECK_IF_USER_HAS_EVAL + '?course=' + SPLIT[4] + '&user=' + ENV.current_user.id)
						.then(checkR => checkR.json())
						.then(checkJson => {
							
							if (!!checkJson.success){
								
								console.info('HVL: Respons 200:OK ved sjekk om ' + SPLIT[4] + ' allerede evaluert: ' + JSON.stringify(checkJson,'',' '));
							
								// Det skal leveres dato med, dersom allerede evaluert. 
								console.info('HVL: checkJson.data = ' + JSON.stringify(checkJson.data,'',' '));
																
								if (obj != false){
									// DEV: console.info('HVL: absolute ' + obj.innerText);
									// Dashboard-sida.
									const evalSymbol = makeEvalSymbol(checkJson.data.status,SPLIT[4],json.data.name,'absolute');
									obj.parentNode.appendChild(evalSymbol);
								}else{
								
									if (REGEX_IS_ON_COURSE_ASSIGNMENTS_PAGE.test(window.location.href)){
									
										// Vi er på sida hvor skjema faktisk skal implementeres.
										implementEmneEvalSkjema();
									
									}else{
									
										// Emneside (ikke Dahsboard, kalender etc.) hvor evaluering bare skal markeres.
										
										const evalSymbol = makeEvalSymbol(checkJson.data.status,SPLIT[4],json.data.name);
										
										
										let trgt = document.querySelector('.assignments'); // Menypunktet Oppgaver i venstremenyen
										if (!!trgt){
											trgt.append(evalSymbol);
										}else{
											console.error('HVL: Fant ikke element med klasse=assignments ');
										}
										
										/** TODO: Får ikke ikonet til å vise i headermenyen ved mobilvisning
										// Mobil-size-side
										trgt = document.querySelector('.mobile-header-title div');
										if (!!trgt){
											trgt.parentNode.parentNode.innerHTML = '<div></div>';
											trgt.parentNode.parentNode.querySelector('div').append(evalSymbol);
										}else{
											console.error('HVL: Fant ikke element med klasse=mobile-header-title div');
										}
										*/
									
									}
	
								}
							
							}else{
								console.error('HVL: Sjekk for om bruker allerede har evaluert returnerte feil: ' + checkJson.errormsg);
							}	
							
							
						})
						.catch(e => {
							console.error('HVL: Feil ved sjekk om emne ' + SPLIT[4] + ' allerede er evaluert: [-3] ' + e)
						})
						
						// TODO: Lagre resultatet i sessionStorage, så man slipper nye, unødige kall.
						// HUSK at ved endelig innsending, må enten parameter endres eller hele sessionStorage mottar en clear-kommando
						
						// DEV: console.info('HVL: Mottok open-for-eval-status for ' + SPLIT[4] + ': ' + JSON.stringify(json,'',' ') + ', next up: has-user-already-evaluated?');
					}else if (!!json.data.message){
						console.info('HVL: Dette canvas-kurset er ikke registrert som emne, derfor ingen evaluering');
					}
				}else{
					console.error('HVL: Feil ved sjekk om emne er åpent for evaluering: [-2] ' + json.errormsg + ' Endpoint:' + ENDPOINT_CHECK_IF_OPEN_FOR_EVAL + SPLIT[4]);
				}
			})
			.catch(e => {
				console.error('HVL: Feil ved sjekk om emne er åpent for evaluering: [-1] ' + e)
			})
			/* DEV .finally(() => {
				console.info('HVL: Evalueringssjekk for emne ' + SPLIT[4])
			})**/
		}			
	}
	
	/**
	 * Generering av et lenktet visuelt grensesnitt for å markere at emnet er åpent
	 * for evaluering og med lenke til evalueringen. Settes inn på ønsket sted i sida.
	 *
	 * @param	{bool}		status		Om emnet allerede er avaluert av denne brukeren (true) eller ikke.
	 * @param	{string}	courseNum	Tall-identifikatoren et emne har i sin URL.
	 * @param	{string}	subjectName	Fullt navn på emnet.
	 * @param	{string}	pos			CSS-attributt position sin verdi (f.eks. fixed, absolute).
	 *
	 * @return	HTML-element i objektform.
	 */
	function makeEvalSymbol(status,courseNum,subjectName,pos = 'relative'){
		let symbol = document.createElement('a');
		
		if (status === true){
		
			// Enklest å ikke la symboldet være synlig?
			symbol.style.visibility = 'hidden';
		
			symbol.innerHTML = '&check;';
			symbol.title = `Emnet ${subjectName} har du allerede evaluert!`;
			symbol.style.background = 'none';
			symbol.style.color = 'lightgreen';
			symbol.href = '#';
			symbol.addEventListener('click',() => {
				alert(`Emnet ${subjectName} har du allerede evaluert!`);
			})
		}else{
			symbol.innerHTML = '&star;';
			symbol.title = `Evaluér anonymt emnet ${subjectName}!`;
			symbol.style.background = 'linear-gradient(#ff8881,#eb6851)';
			symbol.style.boxShadow = '.1rem .1rem .6rem rgba(0,0,0,.2)';
			symbol.style.borderRadius = '50%';
			symbol.style.color = 'white';
			symbol.href = `https://hvl.instructure.com/courses/${courseNum}/assignments`;
		}
		
		symbol.style.position = pos;
		
		symbol.style.cursor = 'pointer';
		
		symbol.style.fontWeight = 'bold';
		symbol.style.textShadow = '.02rem .02rem .1rem rgba(0,0,0,.3)'
		
		symbol.style.textDecoration = 'none';
		
		if (pos == 'absolute'){
			// Dashboard
			symbol.style.left = '1.1rem';
			symbol.style.top = '.75rem';
			if (status !== true){
				symbol.style.fontSize = '1.6rem';
				symbol.style.padding = '.05rem .55rem';
				symbol.style.marginTop = '0';
				symbol.classList.add('wiggle'); // En klasse fra vår egen Canvas-css, via v.hvl.no
			}
		}else{
			// Page
			if (status !== true){
				symbol.style.fontSize = '1.2rem';
				symbol.style.padding = '.1rem .3rem .15rem .3rem';
			}
			symbol.style.marginLeft = '.4rem';
			symbol.classList.add('tiltIn'); // En klasse fra vår egen Canvas-css, via v.hvl.no
		}

		return symbol;
	}
	
	
	// R U N
	
	try{

		async function checkIfOpenToEvaluation(){
			setTimeout(() => {
				
				if (REGEX_IS_ON_COURSE_PAGE.test(window.location.href)){
					// Befinner vi oss på en kursside, og ikke på en liste over emner?
					markSubjectForEvaluation(window.location.href);
				}else{
					// Ekstraherer lenkene i emne-elementene på Dashboard og sjekker hvert emne om det skal evalueres.
					let subjectLinks = document.querySelectorAll('.ic-DashboardCard__link, .course-list-course-title-column a');
					subjectLinks.forEach(e => {
						markSubjectForEvaluation(e.href,e);
					})
				}	
				
			},1500);
		};
		
		if (!!ENV.current_user.id){
			checkIfOpenToEvaluation()
		}else{
			console.info('HVL: Ingen ENV.current_user.id tilgjengelig.');
		}
	
	}catch(e){
		console.error('HVL: Feil oppstod ved emneevalueringssjekk: ' + e);
	}

/* } /Bolla Pinnsvin */

	


// h5p-resize
try{
	if (typeof h5pScript == 'undefined'){
		var h5pScript = document.createElement('script');
		h5pScript.setAttribute('charset', 'UTF-8');
		h5pScript.setAttribute('src', 'https://h5p.com/canvas-resizer.js');
		document.body.appendChild(h5pScript);
		console.info('HVL: h5p Canvas-resizer lagt til');
	}
}catch(e){
	console.error('HVL: Kunne ikkje implementera canvas-resizer frå h5p.com: ' + e);
}

// Oversetjingar

try{
	if (document.documentElement.lang == 'nn' || document.documentElement.lang == 'nb'){
		// Norske sider
		
		// Oversettje Student View
		var rwStudViewBtn = document.getElementsByClassName('icon-student-view');
		rwStudViewBtn[0].parentNode.innerHTML = '<i class="icon-student-view"></i> ' + (document.documentElement.lang == 'nn' ? 'Studentvising' : 'Studentvisning');
	}else{
		// Ikkje-norske sider
		try{
			// Hente verktygmenyelementer
			var rwToolMenu = document.getElementById('section-tabs');

			// Pensum-tittel-oversetjing
			var rwPensumTittelElm = rwToolMenu.getElementsByClassName('context_external_tool_55')[0];
			if (rwPensumTittelElm.innerText == 'Pensum'){
				rwPensumTittelElm.title = 'Reading List';
				rwPensumTittelElm.innerText = 'Reading List';
			}
		}catch(e){
			console.error('HVL: Kunne ikke oversette Pensum-tittel til engelsk: ' + e);
		}

	}
}catch(e){
	var rwStudentViewResult = 'Ingen Student View-knapp å oversettje';
}


function rwCreateHVLhjelpIcon(){
	
	// Se også ikon-innsett.php
	let id = 'hvlHjelpIkon';
	if (document.getElementById(id) == null){
		// Sjekker at elementet ikke allerede er lagt til
		// Elementer og oppsett etter Canvas sin standard som kan bli endret uten forvarsel!
		let icon = document.createElement('li');
		icon.id = id;
		icon.className = 'menu-item ic-app-header__menu-list-item';
		let iconLink =  document.createElement('a');
		//iconLink.href = 'https://v.hvl.no/canvas/iframe/ressursliste.php?realm=helpicon' + (langStartsWithEn.test(document.documentElement.lang) ? '&swaplang=en' : '');
		iconLink.href = 'https://hvl.instructure.com/courses/12436';
		iconLink.target = '_blank';
		iconLink.className = 'ic-app-header__menu-list-link';
		let iconLabel =  document.createElement('div');
		iconLabel.className = 'menu-item__text';
		let iconLabelText = document.createTextNode((langStartsWithEn.test(document.documentElement.lang) ? 'HVL Help' : 'HVL-hjelp'));
		iconLabel.appendChild(iconLabelText);
		let iconCont =  document.createElement('div');
		iconCont.className = 'menu-item-icon-container';
		// HVL-hjelp-ikon
		let svg = `<svg version="1.1" class="ic-icon-svg ic-icon-svg--calendar" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px"
			viewBox="0 0 340.2 340.2" style="enable-background:new 0 0 340.2 340.2;" xml:space="preserve">
			<path d="M102.4,248.5 M179.9,269.4l-27.5-5.5c0,0-14.5,11.8-17.3,14.3c-4.8,4.3-20.7,17.9-20.7,17.9
				c-1.6-5.2-3-10.4-4.4-15.5l7.3-6.4c-1.3-3.8-4.7-21.9-4.7-21.9l-10.3-3.8c-2.4-11.1-4.1-20.6-5.3-27.5l0,0c2.3,2.9,4.9,5.7,7.7,8.3
				c16.5,15.6,39,24.1,63.4,24.1c15.9,0,30.8-3.5,43.7-9.6l0.2-0.1c11.1,15.9,26.7,34.4,37.5,73.3l0,0c-24.8,13.6-53.2,21.4-83.1,21.4
				c-13.2,0-26.1-1.4-38.5-4.2l0,0c-1.6-3.7-3.1-7.6-4.5-11.4c0,0,23.4-23.8,27.8-28C155.7,290.6,179.9,269.4,179.9,269.4z"/>
			<path d="M127.9,334.2C110.7,293,100.9,243.3,97.1,221l0,0c-11.3-14-17.3-31.3-17.3-49.7c0-20.5,9.5-41.4,26-57.4
				c7-6.8,15-12.4,23.6-16.7l0,0c-2-6.6-4.2-12.6-6.4-18.3l0,0C85.1,97.4,62,135.3,62,171.3c0,14.7,3.1,28.9,8.9,41.7
				c-23.7,2.6-39.7,20.7-39.7,20.7c-7-16.5-10.6-34.6-10.6-53.3c0-38.4,15.4-74.4,43.2-101.6C76.7,66.3,91.7,56.2,108.3,49l0,0
				c-3.7-6.4-7.2-11.7-10.3-16.4l0,0c-17,7.9-32.5,18.6-46,31.7C20.3,95.2,2.8,136.4,2.8,180.5c0,43.3,17,83.4,47.7,112.9
				C71.9,313.8,98.6,327.8,127.9,334.2L127.9,334.2z"/>
			<path d="M98.5,33.5c9.1,13.8,21.4,32.9,30.8,63.7l0,0c13.4-6.7,28.5-10.3,44.2-10.3c50.4,0,85.6,35.3,85.6,85.9
				c0,31.1-18.6,57.5-47,71l0,0c3.4,4.9,6.9,10.2,10.8,16l0,0c32.5-17.1,54-49.3,54-87c0-21.2-5.2-41-15-57.5c0,0,17.2-18.5,33.2-20
				c16,22.9,24.6,50.3,24.6,79.3c0,37.5-16.1,73.4-45.2,101.1c-9.4,8.9-20,16.6-31.2,23l-0.1-0.3c2.2,5.7,4.4,11.9,6.5,18.7l0,0
				c51.9-28.5,87.8-82.3,87.8-142.4c0-43.5-16.7-83.7-47-113.2c-30.1-29.4-71-45.6-115.3-45.6c-27.4,0-53.6,5.8-77.1,16.8L98.5,33.5z"/>
			</svg>`;
		iconCont.innerHTML = svg;

		// Montering
		iconLink.appendChild(iconCont);
		iconLink.appendChild(iconLabel);
		icon.appendChild(iconLink);
		// Legg ut i sida
		let m = document.getElementById('menu');
		if (m != null){
			try {
				m.insertBefore(icon,document.getElementById('context_external_tool_1_menu_item'));
			}catch(e){
				try {
					m.appendChild(icon);
				}catch(e){
					console.error('HVL: Kunne ikke sette inn hjelpe-ikon over bunn i meny [-2] ' + e);
				}
			}
		}
		
	}
	return true;
}

// Finn Nyttige lenker og oversett ev. til engelsk.

if (langStartsWithEn.test(document.documentElement.lang)){
	let nyttigeLenker = new RegExp('Nyttige lenker','i');
	let menuItems = document.querySelector('#menu').querySelectorAll('.menu-item__text');
	menuItems.forEach(function(elm){
		if (nyttigeLenker.test(elm.innerText)){
			elm.innerText = 'Helpful Links'
		}
	})	
}


function rwSlideHelp(){
	$('#nav-tray-portal').html('<h1>Test</h1>');
	$('#nav-tray-portal').slideToggle();
}


if (typeof rwInsertHVLicon == 'undefined'){
	let rwInsertHVLicon = rwCreateHVLhjelpIcon();
}

/**
 * En klasse på h1 som fører til dårlig orddeling blir fjernet.
 * @contact	Aasmund Kvamme, 27. oktober -23.
 */
(function() {
    try{
        let classRemoveTrgt = document.querySelector('h1.ic-Action-header__Heading');
        if (!!classRemoveTrgt){
        	classRemoveTrgt.classList.remove('ic-Action-header__Heading');
        }
    }catch(e){
        console.error('HVL: Feil ved endring av klassenamn: ' + e);
    }
})();


(function () {
		
			// Sjekker at elementet ikke allerede er lagt til
			if (document.getElementById('biblioteket') == null){
				// Elementer og oppsett etter Canvas sin standard som kan bli endret uten forvarsel!
				var bibIkon = document.createElement('li');
				bibIkon.style.borderTop = 'dotted 1px #acb7b2';
				bibIkon.id = 'biblioteket';
				bibIkon.className = 'menu-item ic-app-header__menu-list-item';
				bibIkon.style.marginTop = '1rem';
				bibIkon.style.paddingTop = '.1rem';
				let bibIkonLink =  document.createElement('a');
				bibIkonLink.href = (langStartsWithEn.test(document.documentElement.lang) ? 'https://www.hvl.no/en/library/' : 'https://www.hvl.no/bibliotek/');
				bibIkonLink.target = '_blank';
				bibIkonLink.className = 'ic-app-header__menu-list-link';
				let bibIkonLabel =  document.createElement('div');
				bibIkonLabel.className = 'menu-item__text';
				bibIkonLabel.style.fontSize = '70%';
				let bibIkonLabelText = document.createTextNode((langStartsWithEn.test(document.documentElement.lang) ? 'Library' : 'Biblioteket'));
				bibIkonLabel.appendChild(bibIkonLabelText);
				let bibIkonCont =  document.createElement('div');
				bibIkonCont.className = 'menu-item-icon-container';
		bibIkonCont.innerHTML = atob('PD94bWwgdmVyc2lvbj0iMS4wIiBlbmNvZGluZz0idXRmLTgiPz4KPCEtLSBHZW5lcmF0b3I6IEFkb2JlIElsbHVzdHJhdG9yIDI0LjIuMCwgU1ZHIEV4cG9ydCBQbHVnLUluIC4gU1ZHIFZlcnNpb246IDYuMDAgQnVpbGQgMCkgIC0tPgo8c3ZnIHZlcnNpb249IjEuMSIgaWQ9ImJpYmxpb3Rla0lrb24iIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyIgeG1sbnM6eGxpbms9Imh0dHA6Ly93d3cudzMub3JnLzE5OTkveGxpbmsiIHg9IjBweCIgeT0iMHB4IgoJIHZpZXdCb3g9IjAgMCA0MjUuMiA0MjUuMiIgc3R5bGU9ImVuYWJsZS1iYWNrZ3JvdW5kOm5ldyAwIDAgNDI1LjIgNDI1LjI7IiB4bWw6c3BhY2U9InByZXNlcnZlIj4KPHN0eWxlIHR5cGU9InRleHQvY3NzIj4KCS5mYXJnZXtmaWxsOiNmZWZlZmU7fQo8L3N0eWxlPgo8Zz4KCTxnPgoJCTxwYXRoIGNsYXNzPSJmYXJnZSIgZD0iTTIxMyw0MjIuMWMtNi4yLDAtMTIuMS0zLjEtMTYuMS04LjVsLTEyLjYtMTYuOWMwLTAuMS0wLjEtMC4xLTAuMS0wLjFjLTAuNS0wLjgtMS4zLTEuMS0yLjEtMS4xSDUxLjYKCQkJYy0yLjIsMC0zLjktMi0zLjktNC41VjE5MC41YzAtMi41LDEuOC00LjUsMy45LTQuNWgxOS44YzIuMiwwLDMuOSwyLDMuOSw0LjVjMCwyLjUtMS44LDQuNS0zLjksNC41SDU1LjV2MTkxLjNoMTI2LjcKCQkJYzMuMiwwLDYuMiwxLjYsOC4yLDQuNWwxMi41LDE2LjhjMi42LDMuNCw2LjIsNS40LDEwLjIsNS40czcuNi0xLjksMTAuMi01LjRsMTIuNS0xNi44YzItMi45LDQuOS00LjUsOC4yLTQuNWgxMjYuN1YxOTVoLTE1LjkKCQkJYy0yLjIsMC0zLjktMi0zLjktNC41YzAtMi41LDEuOC00LjUsMy45LTQuNWgxOS44YzIuMiwwLDMuOSwyLDMuOSw0LjV2MjAwLjNjMCwyLjUtMS44LDQuNS0zLjksNC41SDI0My45Yy0wLjgsMC0xLjUsMC40LTIuMSwxLjEKCQkJYzAsMC4xLTAuMSwwLjEtMC4xLDAuMWwtMTIuNiwxNi45QzIyNS4xLDQxOSwyMTkuMiw0MjIuMSwyMTMsNDIyLjF6Ii8+Cgk8L2c+Cgk8Zz4KCQk8Zz4KCQkJPGc+CgkJCQk8Zz4KCQkJCQk8cGF0aCBjbGFzcz0iZmFyZ2UiIGQ9Ik0yMTMsMzk4LjljLTEuMiwwLTIuMy0wLjYtMy4xLTEuN2wtMTAuMi0xNWMtNC4zLTYuMi0xMC43LTkuOC0xNy42LTkuOEg3MS4zYy0yLjIsMC0zLjktMi0zLjktNC41VjE1OS45CgkJCQkJCWMwLTIuNSwxLjgtNC41LDMuOS00LjVoMTEwLjZjMTMuNSwwLDI1LjMsOSwzMS4xLDIyYzUuOC0xMy4xLDE3LjYtMjIsMzEuMS0yMmgxMTAuNmMyLjIsMCwzLjksMiwzLjksNC41djIwNy45CgkJCQkJCWMwLDIuNS0xLjgsNC41LTMuOSw0LjVIMjQzLjljLTYuOSwwLTEzLjMsMy42LTE3LjYsOS44bC0xMC4yLDE1QzIxNS4zLDM5OC4yLDIxNC4yLDM5OC45LDIxMywzOTguOXogTTc1LjIsMzYzLjNoMTA2LjkKCQkJCQkJYzkuMywwLDE3LjksNC45LDIzLjcsMTMuM2w3LjEsMTAuNWw3LjItMTAuNWM1LjgtOC41LDE0LjQtMTMuMywyMy43LTEzLjNoMTA2LjlWMTY0LjRIMjQ0LjFjLTE1LDAtMjcuMiwxNC4yLTI3LjIsMzEuNnYxNzEuOAoJCQkJCQljMCwyLjUtMS44LDQuNS0zLjksNC41Yy0yLjIsMC0zLjktMi0zLjktNC41VjE5Ni4xYzAtMTcuNC0xMi4yLTMxLjYtMjcuMi0zMS42SDc1LjJWMzYzLjN6Ii8+CgkJCQk8L2c+CgkJCTwvZz4KCQkJPGc+CgkJCQk8cGF0aCBjbGFzcz0iZmFyZ2UiIGQ9Ik0yMTMsMzk4LjljLTIuMiwwLTMuOS0yLTMuOS00LjV2LTMxLjVjMC0yLjUsMS44LTQuNSwzLjktNC41czMuOSwyLDMuOSw0LjV2MzEuNQoJCQkJCUMyMTYuOSwzOTYuOSwyMTUuMiwzOTguOSwyMTMsMzk4Ljl6Ii8+CgkJCTwvZz4KCQk8L2c+CgkJPGc+CgkJCTxwYXRoIGNsYXNzPSJmYXJnZSIgZD0iTTIxMy40LDE5NWgtMC43Yy0yLjIsMC0zLjktMi0zLjktNC41YzAtMi41LDEuOC00LjUsMy45LTQuNWgwLjdjMi4yLDAsMy45LDIsMy45LDQuNQoJCQkJQzIxNy4zLDE5MywyMTUuNSwxOTUsMjEzLjQsMTk1eiIvPgoJCTwvZz4KCQk8Zz4KCQkJPHBhdGggY2xhc3M9ImZhcmdlIiBkPSJNMjEzLDM5OC45Yy0xLjIsMC0yLjMtMC42LTMuMS0xLjdsLTEwLjItMTVjLTQuMy02LjItMTAuNy05LjgtMTcuNi05LjhINzEuM2MtMi4yLDAtMy45LTItMy45LTQuNVYxNTkuOQoJCQkJYzAtMi41LDEuOC00LjUsMy45LTQuNWgxMTAuNmMxMy41LDAsMjUuMyw5LDMxLjEsMjJjNS44LTEzLjEsMTcuNi0yMiwzMS4xLTIyaDExMC42YzIuMiwwLDMuOSwyLDMuOSw0LjV2MjA3LjkKCQkJCWMwLDIuNS0xLjgsNC41LTMuOSw0LjVIMjQzLjljLTYuOSwwLTEzLjMsMy42LTE3LjYsOS44bC0xMC4yLDE1QzIxNS4zLDM5OC4yLDIxNC4yLDM5OC45LDIxMywzOTguOXogTTc1LjIsMzYzLjNoMTA2LjkKCQkJCWM5LjMsMCwxNy45LDQuOSwyMy43LDEzLjNsNy4xLDEwLjVsNy4yLTEwLjVjNS44LTguNSwxNC40LTEzLjMsMjMuNy0xMy4zaDEwNi45VjE2NC40SDI0NC4xYy0xNSwwLTI3LjIsMTQuMi0yNy4yLDMxLjZ2MTcxLjgKCQkJCWMwLDIuNS0xLjgsNC41LTMuOSw0LjVjLTIuMiwwLTMuOS0yLTMuOS00LjVWMTk2LjFjMC0xNy40LTEyLjItMzEuNi0yNy4yLTMxLjZINzUuMlYzNjMuM3oiLz4KCQk8L2c+CgkJPGc+CgkJCTxwYXRoIGNsYXNzPSJmYXJnZSIgZD0iTTIxMywzOTguOWMtMi4yLDAtMy45LTItMy45LTQuNXYtMzEuNWMwLTIuNSwxLjgtNC41LDMuOS00LjVzMy45LDIsMy45LDQuNXYzMS41CgkJCQlDMjE2LjksMzk2LjksMjE1LjIsMzk4LjksMjEzLDM5OC45eiIvPgoJCTwvZz4KCQk8Zz4KCQkJPGc+CgkJCQk8cGF0aCBjbGFzcz0iZmFyZ2UiIGQ9Ik0zMjQuOSwxOTguOEgyNDJjLTIuMiwwLTMuOS0yLTMuOS00LjVjMC0yLjUsMS44LTQuNSwzLjktNC41aDgyLjljMi4yLDAsMy45LDIsMy45LDQuNQoJCQkJCUMzMjguOCwxOTYuNywzMjcsMTk4LjgsMzI0LjksMTk4Ljh6Ii8+CgkJCTwvZz4KCQkJPGc+CgkJCQk8cGF0aCBjbGFzcz0iZmFyZ2UiIGQ9Ik0zMjQuOSwyMjYuNkgyNDJjLTIuMiwwLTMuOS0yLTMuOS00LjVjMC0yLjUsMS44LTQuNSwzLjktNC41aDgyLjljMi4yLDAsMy45LDIsMy45LDQuNQoJCQkJCUMzMjguNywyMjQuNiwzMjcsMjI2LjYsMzI0LjksMjI2LjZ6Ii8+CgkJCTwvZz4KCQkJPGc+CgkJCQk8cGF0aCBjbGFzcz0iZmFyZ2UiIGQ9Ik0zMjQuOSwyNTQuNUgyNDJjLTIuMiwwLTMuOS0yLTMuOS00LjVjMC0yLjUsMS44LTQuNSwzLjktNC41aDgyLjljMi4yLDAsMy45LDIsMy45LDQuNQoJCQkJCUMzMjguNywyNTIuNSwzMjcsMjU0LjUsMzI0LjksMjU0LjV6Ii8+CgkJCTwvZz4KCQkJPGc+CgkJCQk8cGF0aCBjbGFzcz0iZmFyZ2UiIGQ9Ik0zMjQuOSwyODIuNEgyNDJjLTIuMiwwLTMuOS0yLTMuOS00LjVjMC0yLjUsMS44LTQuNSwzLjktNC41aDgyLjljMi4yLDAsMy45LDIsMy45LDQuNQoJCQkJCUMzMjguOCwyODAuMywzMjcsMjgyLjQsMzI0LjksMjgyLjR6Ii8+CgkJCTwvZz4KCQkJPGc+CgkJCQk8cGF0aCBjbGFzcz0iZmFyZ2UiIGQ9Ik0zMjQuOSwzMTAuMkgyNDJjLTIuMiwwLTMuOS0yLTMuOS00LjVjMC0yLjUsMS44LTQuNSwzLjktNC41aDgyLjljMi4yLDAsMy45LDIsMy45LDQuNQoJCQkJCUMzMjguOCwzMDguMiwzMjcsMzEwLjIsMzI0LjksMzEwLjJ6Ii8+CgkJCTwvZz4KCQkJPGc+CgkJCQk8cGF0aCBjbGFzcz0iZmFyZ2UiIGQ9Ik0zMjQuOSwzMzguMUgyNDJjLTIuMiwwLTMuOS0yLTMuOS00LjVjMC0yLjUsMS44LTQuNSwzLjktNC41aDgyLjljMi4yLDAsMy45LDIsMy45LDQuNQoJCQkJCUMzMjguOCwzMzYuMSwzMjcsMzM4LjEsMzI0LjksMzM4LjF6Ii8+CgkJCTwvZz4KCQk8L2c+Cgk8L2c+CjwvZz4KPC9zdmc+Cg==');
		
				// Montering
				bibIkonLink.appendChild(bibIkonCont);
				bibIkonLink.appendChild(bibIkonLabel);
				bibIkon.appendChild(bibIkonLink);
				// Legg ut i sida
				let m = document.getElementById('menu');
				if (m != null){
					m.appendChild(bibIkon);
				}

}
		
			// Sjekker at elementet ikke allerede er lagt til
			if (document.getElementById('seiifraa') == null){
				// Elementer og oppsett etter Canvas sin standard som kan bli endret uten forvarsel!
				var seiIkon = document.createElement('li');
				//seiIkon.style.borderTop = 'dotted 1px #acb7b2';
				seiIkon.id = 'seiifraa';
				seiIkon.className = 'menu-item ic-app-header__menu-list-item';
				//seiIkon.style.marginTop = '1rem';
				seiIkon.style.paddingTop = '.1rem';
				let seiIkonLink =  document.createElement('a');
				seiIkonLink.href = (langStartsWithEn.test(document.documentElement.lang) ? 'https://www.hvl.no/en/tell-us-about-the-learning-environment-at-hvl/' : 'https://www.hvl.no/sei-ifra/');
				seiIkonLink.target = '_blank';
				seiIkonLink.className = 'ic-app-header__menu-list-link';
				let seiIkonLabel =  document.createElement('div');
				seiIkonLabel.className = 'menu-item__text';
				let seiIkonLabelText = document.createTextNode((langStartsWithEn.test(document.documentElement.lang) ? 'Tell Us!' : 'Sei ifrå!'));
				seiIkonLabel.appendChild(seiIkonLabelText);
				let seiIkonCont =  document.createElement('div');
				seiIkonCont.className = 'menu-item-icon-container';
		seiIkonCont.innerHTML = atob('PD94bWwgdmVyc2lvbj0iMS4wIiBlbmNvZGluZz0idXRmLTgiPz4NDQo8IS0tIEdlbmVyYXRvcjogQWRvYmUgSWxsdXN0cmF0b3IgMjUuMS4wLCBTVkcgRXhwb3J0IFBsdWctSW4gLiBTVkcgVmVyc2lvbjogNi4wMCBCdWlsZCAwKSAgLS0+DQ0KPHN2ZyB2ZXJzaW9uPSIxLjEiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyIgeG1sbnM6eGxpbms9Imh0dHA6Ly93d3cudzMub3JnLzE5OTkveGxpbmsiIHg9IjBweCIgeT0iMHB4Ig0NCgkgdmlld0JveD0iMCAwIDQ2NS4xMSA0NDYuNDgiIHN0eWxlPSJlbmFibGUtYmFja2dyb3VuZDpuZXcgMCAwIDQ2NS4xMSA0NDYuNDg7IiB4bWw6c3BhY2U9InByZXNlcnZlIj4NDQo8c3R5bGUgdHlwZT0idGV4dC9jc3MiPg0NCgkuc3Qwe2ZpbGw6IzAwQjZDOTt9DQ0KCS5zdDF7ZmlsbDojNTU5Q0E3O30NDQoJLnN0MntmaWxsOiNGRkZGRkY7fQ0NCgkuc3Qze2ZpbGw6I0Q5RDlEODt9DQ0KCS5zdDR7ZmlsbDojRkZGRkZGO3N0cm9rZTojRkZGRkZGO3N0cm9rZS1taXRlcmxpbWl0OjEwO30NDQo8L3N0eWxlPg0NCjxnIGlkPSJzZWlJZnJhYUxhZ18xIj4NDQoJPHBhdGggY2xhc3M9InN0MCIgZD0iTTQ1Ni41MiwyOTQuNGMtNC4yNCwxNS40OS0xNy4xMSwzMi0zNS43Myw0OC4yM2MtMS42NCwxLjQ0LTMuMzMsMi44Ni01LjA2LDQuMjkNDQoJCWMtMzMuMjcsMjcuNDMtODIuMzgsNTMuNy0xMzMuMTgsNzIuMzZsLTAuMDEsMGMtNC4zOCwxLjYxLTguNzcsMy4xNy0xMy4xNiw0LjY2Yy02Mi4zNiwyMS4xNS0xMjUuNDQsMzAuMS0xNjMuNCwxNS4wNQ0NCgkJYy0yMS42Ny04LjU4LTQwLjU1LTI4Ljg4LTU1LjcyLTU1LjU5Yy0xLjI2LTIuMjEtMi40OC00LjQ2LTMuNjktNi43NWMtMjEuNDMtNDAuODMtMzQuNjktOTQuNjYtMzYuOTEtMTQ0LjY2DQ0KCQljLTAuMjMtNS40NS0wLjM1LTEwLjg1LTAuMzItMTYuMTljMC4yMS00Ni40MywxMC42NC04Ny43NywzMy43My0xMDkuNjlDNjkuNjIsODAuOSwxMjguOTgsNzEuMDksMTkwLjUsNzEuMzINDQoJCWM1LjUyLDAuMDIsMTEuMDUsMC4xMiwxNi41OCwwLjNjNTguODcsMS45MSwxMTcuMiwxMi41MywxNDguMzIsMjcuMThjMS4zMywwLjYzLDIuNjcsMS4zMiw0LDIuMDVjMy4wMywxLjY2LDYuMDYsMy42MSw5LjA5LDUuOA0NCgkJQzQxOS4wNiwxNDMuMTcsNDY5LjA3LDI0OC41NCw0NTYuNTIsMjk0LjR6Ii8+DQ0KCTxwYXRoIGNsYXNzPSJzdDEiIGQ9Ik0xNi4yMSwyMTUuMjRjLTAuMDMsNS4zMywwLjA4LDEwLjc0LDAuMzIsMTYuMTljMi4yMiw1MCwxNS40OCwxMDMuODIsMzYuOTEsMTQ0LjY2DQ0KCQljMS4yMSwyLjI5LDIuNDMsNC41NCwzLjY5LDYuNzVjMTUuMTcsMjYuNzIsMzQuMDUsNDcuMDEsNTUuNzIsNTUuNTljMTYuNjEsNi41OCwzOC4wNCw4LjU3LDYyLjExLDYuOTUNDQoJCWMtMjYuODYsMi41LTUwLjgsMC44MS02OC45Ny02LjM5Yy0yMS42Ny04LjU4LTQwLjU1LTI4Ljg4LTU1LjcyLTU1LjU5Yy0xLjI2LTIuMjEtMi40OC00LjQ2LTMuNjktNi43NQ0NCgkJYy0yMS40My00MC44My0zNC42OS05NC42Ni0zNi45MS0xNDQuNjZjLTAuMjMtNS40NS0wLjM1LTEwLjg1LTAuMzItMTYuMTljMC4yMS00Ni40MywxMC42NC04Ny43NywzMy43My0xMDkuNjkNDQoJCWMyMC44MS0xOS43Niw2MS43OC0zMC4wNiwxMDguMTUtMzMuNDhjLTQzLjUyLDMuOS04MS41MSwxNC4xMy0xMDEuMjksMzIuOTFDMjYuODUsMTI3LjQ3LDE2LjQyLDE2OC44MSwxNi4yMSwyMTUuMjR6Ii8+DQ0KCTxwYXRoIGNsYXNzPSJzdDIiIGQ9Ik00MjAuNzksMzQyLjYzYy0xLjY0LDEuNDQtMy4zMywyLjg2LTUuMDYsNC4yOWMtNC4xMiwxNC43OS0xMC4zNywyNi43NC0xOC42NiwzNS4yMQ0NCgkJYy0xNS41MSwxNS44My00NC4yMSwyNy40NS04My4wMiwzMy41OWMtOC44NSwxLjQtMTguMTcsMi41LTI3Ljg1LDMuMjljLTEuMjEsMC4xLTIuNDMsMC4xOS0zLjY2LDAuMjdsLTAuMDEsMA0NCgkJYy0yOC4yMiwyLjA4LTU5LjIyLDEuNi05MC4wNC0xLjVjLTQwLjM0LTQuMDUtNzcuODMtMTIuMTUtMTA4LjQyLTIzLjQzYy0xNC40Ni01LjMzLTI3LjA0LTExLjI4LTM3LjUxLTE3LjY5DQ0KCQljLTEzLjMzLTguMTctMjMuMjUtMTcuMDktMjkuMzUtMjYuNTFjLTE0LjA1LTIxLjY5LTE3LjMxLTY1LjEtOC40OS0xMTMuM2MwLjI5LTEuNjMsMC42MS0zLjI0LDAuOTItNC44NQ0NCgkJYzkuMTQtNDYuMDMsMjcuMTYtODQuMyw0Ny41MS0xMDAuNTdDODUuODQsMTA4LjQ4LDE0Ni45Niw4NCwyMDUuNzksNzEuODlsMS4yOS0wLjI3bDEuMS0wLjIzbC0yNy42Mi01OS45Nmw3NS42NCw1My4yN2wwLjY5LTAuMDUNDQoJCWMzMS42NS0yLjE2LDU2Ljg3LDEuMzEsNzIuOTUsMTAuMDJjMTAuNSw1LjY5LDIwLjQxLDE0Ljc0LDI5LjU2LDI2LjE3YzMuMDMsMS42Niw2LjA2LDMuNjEsOS4wOSw1LjgNDQoJCUMzNTcuMzgsOTAuOTIsMzQ1LDc4LjUsMzMxLjY4LDcxLjI4Yy0xNi44NC05LjEyLTQxLjg2LTEyLjY3LTc0LjM3LTEwLjUyTDE3MS4wMywwbDMxLjYsNjguNmMtNC4wNCwwLjg2LTguMDgsMS43Ni0xMi4xMywyLjcyDQ0KCQlDMTM1LjgxLDg0LjM0LDgxLjQ5LDEwNyw1NC43NSwxMjguNGMtMTguNjcsMTQuOTQtMzUuMzgsNDcuNDctNDUuNDEsODcuNDFjLTEuNjcsNi41OS0zLjE0LDEzLjM5LTQuNDIsMjAuMzUNDQoJCWMtOC45OCw0OS4xMi01LjUxLDkzLjYsOS4wNSwxMTYuMWM3Ljc2LDExLjk4LDIwLjQ3LDIyLjMzLDM2LjI5LDMxLjE0YzM3Ljc2LDIxLjA2LDkzLjMsMzMuMzUsMTQxLjg1LDM4LjIzDQ0KCQljMjYuMiwyLjYzLDUyLjU2LDMuMzksNzcuMjYsMi4zMWMxNS45NC0wLjcsMzEuMi0yLjE3LDQ1LjI4LTQuNGMzOS42My02LjI3LDY5LjA4LTE4LjI3LDg1LjE4LTM0LjcNDQoJCUM0MDkuNTQsMzc0LjkzLDQxNi41OCwzNjAuNTQsNDIwLjc5LDM0Mi42M3oiLz4NDQoJPGc+DQ0KCQk8cGF0aCBjbGFzcz0ic3QzIiBkPSJNOS42NiwyMzEuOTljOS4xNC00Ni4wMywyNy4xNi04NC4zLDQ3LjUxLTEwMC41N0M4NS44NCwxMDguNDgsMTQ2Ljk2LDg0LDIwNS43OSw3MS44OWwxLjI5LTAuMjcNDQoJCQljMS44NSwwLjA2LDMuNzEsMC4xMyw1LjU2LDAuMjFsMS4xLDIuMzhsLTQuNzgsMC45OGwtMi4zOSwwLjVjLTU4LjI5LDExLjk5LTExOC43MywzNi4xNS0xNDYuOTgsNTguNzYNDQoJCQljLTE5LjUxLDE1LjYtMzcuMTksNTMuMjYtNDYuMTMsOTguMjljLTAuMzEsMS41OS0wLjYyLDMuMTktMC45MSw0LjgxYy0wLjY2LDMuNjItMS4yNSw3LjIxLTEuNzcsMTAuNzYNDQoJCQlDMTAuMjgsMjQyLjg0LDkuOSwyMzcuMzksOS42NiwyMzEuOTl6Ii8+DQ0KCQk8cGF0aCBjbGFzcz0ic3QxIiBkPSJNMjgyLjU0LDQxOS4yN2MtMjguMjIsMi4wOC01OS4yMiwxLjYtOTAuMDQtMS41Yy00MC4zNC00LjA1LTc3LjgzLTEyLjE1LTEwOC40Mi0yMy40Mw0NCgkJCWMtMTQuNDYtNS4zMy0yNy4wNC0xMS4yOC0zNy41MS0xNy42OWMtMS4xNy0yLjI0LTIuMzItNC41Mi0zLjQ0LTYuODNjMS43NSwxLjE5LDMuNTcsMi4zNyw1LjQ3LDMuNTMNDQoJCQljMTAuMjksNi4zLDIyLjY4LDEyLjE1LDM2LjgyLDE3LjM2YzMwLjI5LDExLjE3LDY3LjQ1LDE5LjE5LDEwNy40NywyMy4yMWMzMC4zLDMuMDUsNjEuMiwzLjU3LDg5LjQxLDEuNDkNDQoJCQljMS4yMS0wLjA4LDIuNC0wLjE3LDMuNi0wLjI3YzMuMzYtMC4yOCw2LjY3LTAuNTksOS45My0wLjk0QzI5MS40Miw0MTUuOTUsMjg2Ljk5LDQxNy42NCwyODIuNTQsNDE5LjI3TDI4Mi41NCw0MTkuMjd6Ii8+DQ0KCTwvZz4NDQo8L2c+DQ0KPGcgaWQ9InNlaUlmcmFhTGFnX18zIj4NDQoJPGc+DQ0KCQk8cGF0aCBjbGFzcz0ic3QyIiBkPSJNMzAyLjAzLDI0NS4yMWwtMS44MSwzLjMzbC0yLjAxLDEuNmwtNSwxLjg3bC02LjUsMS45N2wtNi40MywzLjAzbC0yLjA4LDEuMzdsLTAuNzQsMi4zNWwwLjE0LDIuMzYNDQoJCQlsMjAuMjIsOTcuMjNsLTAuNTQsMy4xOGwtMi4zNCwzLjg0bC0yLjQzLDEuNjhsLTIuNzgsMC4yNGwtMi42MS0xLjc3bC0xLjUyLTIuNjlsLTEuMjUtNS4xNWwtMy43NC0xNS40N2wtMjQuODQtMTE5LjA2bDAuMzUtNC44OQ0NCgkJCWwtMC4wNy0zLjkxbDAuNjktMi41N2wxLjc3LTEuNzRsMi4zOS0xLjAxbDQuODEtMC4wNGw0Ljc0LTAuMzFsOS4zMS0yLjI1bDguNDgtMi4wNWwzLjU2LDAuMjVsMi44OSwxLjk3bDEuNjQsMi4yN2wtMC4yNywyLjUyDQ0KCQkJbC0xLjc3LDEuNzdsLTIuNDQsMC44MWwtMTIuODUsMi4yM2wtNC41MiwyLjE4bC0yLjY2LDIuNjFsLTEuMzMsMy41OWwtMC4zNSw0LjAxbDEuMTcsNy41NmwyLjQ0LDcuMjZsMC44OCwxLjc1bDEuNDksMC41MQ0NCgkJCWwyLjMxLTAuOGw3LjY0LTMuNTZsOC4wMy0xLjk0bDMuMTktMC4zM2wyLjQxLDAuOTdMMzAyLjAzLDI0NS4yMXoiLz4NDQoJCTxwYXRoIGNsYXNzPSJzdDIiIGQ9Ik0zNzUuMDIsMzQwLjU3bC0yMS41My0zNS41MmwtOS40My0xOS41NGwtMTIuNzMtMjEuMzVsLTAuOTctMC40MmwtMC4xNiwxLjEzbDAuMzQsMi4zMmw3LjA2LDQzLjYzDQ0KCQkJbDQuNDMsNTAuODFsLTEuNzEsMS45NGwtMi42OCwwLjY1bC0yLjQxLTAuOTRsLTIuMjktMi4yOEwzMzEuOCwzNThsLTI2LjQ4LTE0OS4yM2wxLjEzLTcuMDNsMS4xLTIuNjZsMi43NC0yLjE5bDUuNzItMi40Nw0NCgkJCWw3LjE1LTEuMDdsNy41MS0wLjUxbDUuNSwwLjJsNS42NSwwLjgxbDQuNTYsMS43M2w0LjYsMi41OWw1LjUsNC41NmwzLjMyLDUuMDhsMS41OSw2LjU5bC0wLjQxLDQuMzhsLTIuMTEsNy45N2wtMi42OSwzLjI4DQ0KCQkJbC0zLjU2LDMuMjdsLTUuODIsMy44MmwtNi4xMiwyLjU3bC0yLjQyLDAuOGwtMS4yOSwwLjk3bC0wLjIyLDEuOGwxLjE3LDIuMTJsMjguOTksNTguNmwzMi4xLDYwLjY4bC0wLjQ5LDIuNTJsLTEuNjgsMi4xNQ0NCgkJCWwtMi43NSwwLjQ1bC0yLjc4LTEuNWwtMi4wNS0yLjExbC0xMi44LTIzLjI4TDM3NS4wMiwzNDAuNTd6IE0zMjEuNDIsMjA2Ljg2bDAuMTMsMy4yM2wyLjMyLDE3LjczbDAuNjUsMi42N2wxLjY4LDEuNTVsMi42MSwwLjg5DQ0KCQkJbDQuNjMtMC42OGw0Ljk0LTEuMTlsNC42NC0yLjQzbDMuODMtMy4xbDIuNjUtNC4zNWwxLjE2LTQuODhsMC4xOS0zLjU1bC0xLjItMy4ybC0zLjAzLTIuNzZsLTUuNTUtMi41OGwtNC45OS0xLjc4bC00Ljk5LDAuMzQNDQoJCQlsLTguNiwzLjE3TDMyMS40MiwyMDYuODZ6Ii8+DQ0KCQk8cGF0aCBjbGFzcz0ic3QyIiBkPSJNMzgxLjU5LDM0NC44bC0zLjQ5LTAuNzlsLTEuNjYtMi4zMmwtMC41NS0zLjRsLTAuMy0xOS4yNGwtMC43NS0xOC44NmwwLjY5LTE0NS42OWwyLjE0LTIuNDJsMy4wMSwwLjA5DQ0KCQkJbDIuMzYsMS44OGwxLjUxLDIuOWwyLjA2LDcuMzlsMTIuMzIsMjMuOTVsMTQuMjQsMjQuMDNsMy45Miw3LjIxbDMuNDcsNy41OWw2LjM2LDE1LjA2bDExLjMsMzFsMTAuOTUsMjcuMjdsMTIuODcsMjcuMzUNDQoJCQlsMi41NCwzLjc0bDAuNTUsMy40bC0xLjU3LDIuNTZsLTIuNDcsMi4yM2wtMy4wNSwyLjFsLTMuMjktMC4wMmwtMi45NS0ybC0xLjc4LTIuODNsLTQyLjgtMTA2LjA3bC0yLjAzLTEuN2wtMy4wNC0wLjM2bC0zLjMsMC44DQ0KCQkJbC0xMi40Nyw2LjNsLTEuNjYsMi4wNGwwLjI0LDMuMjJsMC41LDEwMC4yNEwzODEuNTksMzQ0Ljh6IE0zODIuODEsMTg1LjY1bC0wLjg2LDMuMmwwLjE0LDUuMTNsMS4zNiwyMS40M2wxLjIxLDEuNjFsMi40NC0wLjA1DQ0KCQkJbDQuMjQtMC40OGw1LjY1LTEuMzdsMS4yMy0xLjY1bC0wLjM2LTIuNjNsLTEuNTQtMi44OGwtNS4wOS05LjM1bC02LjgxLTEyLjAxTDM4Mi44MSwxODUuNjV6Ii8+DQ0KCQk8Zz4NDQoJCQk8Zz4NDQoJCQkJPHBhdGggY2xhc3M9InN0MiIgZD0iTTM2My43NiwxNTAuMTZsLTEuMDEtMC42OGwtMS4wNi0xLjgxbC0wLjU4LTEuODVsLTAuODEtMy43MWwtMS4yMi05LjMybDAuMDgtOS45N2wwLjY2LTEuMDRsMS4zNS0xLjENDQoJCQkJCWwxLjQyLTAuODdsMS40LTAuNzdsMi4wNS0wLjgzbDIuNC0wLjYxbDIuNTgtMC40OWwyLjM1LDBsMS45MiwwLjM1bDEuMywwLjk2bDIuNTEsMTAuNTdsMS42MSwxMi44OGwtMC4yMSwxLjE4bC0wLjQxLDEuMjYNDQoJCQkJCWwtMC42MywwLjkzbC0xLjAxLDAuODdsLTEuNDMsMC43OGwtMi45MywxLjE5bC0zLjA0LDEuMDNsLTMuMTIsMC44bC0yLjI0LDAuMzdMMzYzLjc2LDE1MC4xNnogTTM2OS43OCwxNDQuMDRsMS4zNSwwLjINDQoJCQkJCWwxLjAxLDAuNTZsMC4wMiwwLjI4bDAuOTQtMC4xOWwyLjUzLTAuOWwwLjM1LTAuMjhsMC4xMS0wLjI1bC0wLjMxLTYuMzFsLTAuOTgtNi4yMWwtMi4zNC0xMC44MWwtMC4yNi0wLjNsLTAuNzktMC4yMw0NCgkJCQkJbC0xLjY0LDAuMDFsLTEuNjksMC4zM2wtMS40NCwwLjUzbC0xLjM2LDAuODRsLTEuMDEsMC43NWwtMC41MSwwLjY4bC0wLjI2LDIuNDZsMC4wNywyLjM5bDAuNzQsNy45NGwxLjE3LDMuNzFsMS41MiwzLjQ1DQ0KCQkJCQlsMC42MiwwLjU2bDAuODcsMC40OUwzNjkuNzgsMTQ0LjA0eiIvPg0NCgkJCQk8cGF0aCBjbGFzcz0ic3QyIiBkPSJNMzcxLjEyLDExNy42bDIuMjYsMC4wMWwxLjcyLDAuMzJsMS4wNiwwLjc4bDIuNDcsMTAuMzlsMS41OSwxMi43NWwtMC4xOSwxLjFsLTAuMzcsMS4xMmwtMC41NSwwLjgyDQ0KCQkJCQlsLTAuOTIsMC43OWwtMS4zOCwwLjc2bC0yLjg4LDEuMTZsLTMuMDEsMS4wMmwtMy4wOSwwLjc5bC0yLjE0LDAuMzVsLTEuNzQtMC4xbC0wLjgtMC41NGwtMC45OC0xLjY3bC0wLjU3LTEuOGwtMC43OS0zLjY2DQ0KCQkJCQlsLTEuMjEtOS4yOGwwLjA4LTkuNzZsMC41NC0wLjg1bDEuMjktMS4wNWwxLjM2LTAuODJsMS4zOC0wLjc2bDItMC44MWwyLjM0LTAuNTlMMzcxLjEyLDExNy42IE0zNzEuOTQsMTQ1LjY0bDEuMjctMC4yNg0NCgkJCQkJbDIuNjctMC45NWwwLjUtMC40MWwwLjIxLTAuNDZsLTAuMzItNi40M2wtMC45OC02LjI2bC0yLjM3LTEwLjk2bC0wLjQzLTAuNTFsLTEtMC4yOWwtMS43MSwwLjAxbC0xLjc4LDAuMzRsLTEuNTIsMC41NQ0NCgkJCQkJbC0xLjQ1LDAuODhsLTEuMSwwLjgybC0wLjY0LDAuODVsLTAuMjcsMi42bDAuMDYsMi40NmwwLjc1LDguMDNsMS4xOCwzLjc2bDEuNTgsMy42bDAuNzQsMC42NmwwLjk4LDAuNTVsMS4zNSwwLjMzbDEuMzEsMC4yDQ0KCQkJCQlsMC43LDAuMzhsMC4wMywwLjM1TDM3MS45NCwxNDUuNjQgTTM3MS4wMywxMTYuNTlsLTAuMDksMC4wMmwtMi41NSwwLjQ4bC0wLjAzLDAuMDFsLTAuMDMsMC4wMWwtMi4zNCwwLjU5bC0wLjA3LDAuMDINDQoJCQkJCWwtMC4wNiwwLjAzbC0yLDAuODFsLTAuMDUsMC4wMmwtMC4wNSwwLjAzbC0xLjM4LDAuNzZsLTEuNCwwLjg1bC0wLjA2LDAuMDRsLTAuMDUsMC4wNGwtMS4yOSwxLjA1bC0wLjEzLDAuMWwtMC4wOSwwLjE0DQ0KCQkJCQlsLTAuNTQsMC44NWwtMC4xNSwwLjI0bDAsMC4yOWwtMC4wOCw5Ljc2bDAsMC4wN2wwLjAxLDAuMDdsMS4yMSw5LjI4bDAuMDEsMC4wNGwwLjAxLDAuMDRsMC43OSwzLjY2bDAuMDEsMC4wNGwwLjAxLDAuMDQNDQoJCQkJCWwwLjU3LDEuOGwwLjAzLDAuMTFsMC4wNiwwLjFsMC45OCwxLjY3bDAuMTIsMC4ybDAuMTksMC4xM2wwLjgsMC41NGwwLjIzLDAuMTVsMC4yNywwLjAybDEuNzQsMC4xbDAuMTEsMC4wMWwwLjExLTAuMDINDQoJCQkJCWwyLjE0LTAuMzVsMC4wNC0wLjAxbDAuMDQtMC4wMWwzLjA5LTAuNzlsMC4wNC0wLjAxbDAuMDQtMC4wMWwzLjAxLTEuMDJsMC4wMy0wLjAxbDAuMDMtMC4wMWwyLjg4LTEuMTZsMC4wNi0wLjAybDAuMDUtMC4wMw0NCgkJCQkJbDEuMzgtMC43NmwwLjA5LTAuMDVsMC4wOC0wLjA3bDAuOTItMC43OWwwLjEtMC4wOWwwLjA4LTAuMTFsMC41NS0wLjgybDAuMDgtMC4xMmwwLjA0LTAuMTNsMC4zNy0xLjEybDAuMDItMC4wN2wwLjAxLTAuMDcNDQoJCQkJCWwwLjE5LTEuMWwwLjAzLTAuMTVsLTAuMDItMC4xNWwtMS41OS0xMi43NWwtMC4wMS0wLjA1bC0wLjAxLTAuMDVsLTIuNDctMTAuMzlsLTAuMDgtMC4zNmwtMC4yOS0wLjIybC0xLjA2LTAuNzhsLTAuMTktMC4xNA0NCgkJCQkJbC0wLjIzLTAuMDRsLTEuNzItMC4zMmwtMC4wOS0wLjAybC0wLjA5LDBsLTIuMjYtMC4wMUwzNzEuMDMsMTE2LjU5TDM3MS4wMywxMTYuNTl6IE0zNjkuODgsMTIwLjExbDEuNDctMC4wMWwwLjU4LDAuMTcNDQoJCQkJCWwwLjA4LDAuMDlsMi4zMSwxMC42OGwwLjk3LDYuMThsMC4zLDYuMTRsLTAuMDEsMC4wM2wtMC4xOSwwLjE1bC0yLjQ0LDAuODdsLTAuMzQsMC4wN2wtMC40NS0wLjI1bC0wLjctMC4zOGwtMC4xNi0wLjA5DQ0KCQkJCQlsLTAuMTgtMC4wM2wtMS4yNi0wLjE5bC0xLjE3LTAuMjlsLTAuNzYtMC40M2wtMC41LTAuNDVsLTEuNDgtMy4zNWwtMS4xNC0zLjZsLTAuNzMtNy44OWwtMC4wNi0yLjM2bDAuMjQtMi4yNmwwLjM4LTAuNTENDQoJCQkJCWwwLjk1LTAuN2wxLjMzLTAuOGwxLjM1LTAuNDlMMzY5Ljg4LDEyMC4xMUwzNjkuODgsMTIwLjExeiIvPg0NCgkJCTwvZz4NDQoJCTwvZz4NDQoJCTxnPg0NCgkJCTxwYXRoIGNsYXNzPSJzdDIiIGQ9Ik0yMzEuNjEsMjI0LjQzbDIuNDctMS4zMmwyLjg2LDAuMjJsMi4wMywxLjU5bDAuNjYsMi42NGwzMC44NSwxNzEuNDJsLTEuODcsMS44N2wtMi42NCwwLjY2bC0zLjA4LTEuMQ0NCgkJCQlsLTIuNDctMi40MmwtMS4xNS0yLjc1bC0xNS4yNC03Ni4wN2wtOC44OC0zNy40OGwtOS42LTM3LjU0bC0wLjQ0LTQuNTZsMC40NC0yLjkxTDIzMS42MSwyMjQuNDN6Ii8+DQ0KCQkJPHBhdGggY2xhc3M9InN0NCIgZD0iTTIzMy42LDIwMi4xMmwtMi4zNC00LjlsLTAuNzgtMC4xNWwtMS4yMSwwLjI2bC0xLjU1LDAuNTVsLTEuNzMsMC44OGwtMS41NywwLjg5bC0xLjMzLDAuOTJsLTAuOTEsMC43NA0NCgkJCQlsLTAuODksMC43N2wtMC44MiwwLjg4bC0wLjMyLDAuNTlsMC41NSw1LjE5bDEuNDEsNC42MmwwLjc3LDEuNzVsMC41LDAuODFsMC43OCwwLjY0bDAuNTgsMC4wOWwxLjIxLTAuMzhsMS40NS0wLjcxbDIuMDgtMS4xOA0NCgkJCQlsMi4wMS0xLjI4bDEuOTEtMS4zMmwwLjkxLTAuNzRsMC41OS0wLjY1bDAuMzMtMC41N2wwLjE4LTAuNjhsMC4wNi0wLjYzTDIzMy42LDIwMi4xMnogTTIyMy44MSwyMDkuMjJsLTAuMTItMC40OQ0NCgkJCQljMC4wNywwLjI4LDAuMjUsMC42NSwwLjQ0LDEuMDFMMjIzLjgxLDIwOS4yMnogTTIyOS4yOCwxOTkuMTdsLTIuMzktMC4xN2wtNC4yNiwzLjQyYzAsMCwwLjg3LDUuNTMsMS4wNiw2LjMxbC0wLjg5LTMuNTgNDQoJCQkJbC0wLjItMS4yOWwwLjAzLTEuNDRsMC4zOS0wLjYxbDAuNzEtMC43bDAuOTUtMC44MmwxLjAxLTAuNjdsMS4yLTAuNjJsMS4xOC0wLjQzbDAuNzEtMC4wOWwwLjMzLDAuMTZsMC4yOSwwLjY2TDIyOS4yOCwxOTkuMTd6DQ0KCQkJCSBNMjMyLjk4LDIxMC41OGwtMC4zMiwwLjM0bC0wLjgxLDAuNTJjMC4zNS0wLjY4LDAuODQtMS42NSwxLjA3LTIuMTNsMC4xOCwwLjk3TDIzMi45OCwyMTAuNTh6Ii8+DQ0KCQk8L2c+DQ0KCTwvZz4NDQoJPGc+DQ0KCQk8cGF0aCBjbGFzcz0ic3QyIiBkPSJNMTQ2LjIsMTY1LjAybC0wLjU0LDIuNDdsLTEuOTcsMi4zNmwtMi40LDEuNTNsLTExLjY0LDMuNmwtMTEuNTksMi44OWwtNS4xMywxLjc2bC0xLjc0LDEuMzlsLTAuNDgsMS43OQ0NCgkJCWwwLjYyLDIuNDdsMi4xNCw0Ljk1bDIuNzYsOC4zN2wxLjE0LDEuODZsMS40OSwwLjU4bDIuMjItMS4wMmwxMC0zLjlsNS45Ny0xLjk2bDYuNDEtMS4xM2wzLjMxLDAuMzRsMi4zNCwxLjA1bDEuMjYsMi4yNg0NCgkJCWwwLjY2LDIuNjRsLTAuMTYsMy4wN2wtMS42MywyLjc0bC0yLjgzLDEuNjRsLTEwLjQ5LDMuMzJsLTUuMzksMS41NmwtNS4xMywybC0yLjQyLDEuNTRsLTAuOTQsMS44N2w2LjY5LDI2LjgybDYuMDksMjQuNA0NCgkJCWwxMC4yNSw1Mi4zMmwyLjA5LDEuODFsMy4wOCwxLjFsNC4yMywwLjExbDkuNDUtMi4zNmw3LjQyLTEuMTVsMi45MSwwLjQ0bDIuMzEsMS43NmwwLjg4LDIuNThsMS45OCwyLjMxbDIuMDksMS44MWwwLjY2LDIuNjQNDQoJCQlsLTAuOTQsMi44bC0yLjUzLDIuMDNsLTIuOTEsMS40M2wtMTAuOTksMi43NGwtNS4zMywxLjFsLTUuNjYsMC43MWwtNi42NS0wLjQ0bC02LjY1LTMuMjVsLTMuOS0yLjUzbC0yLjItMy4xOWwtMTMuMi03My41NA0NCgkJCWwtOS4wNS0zNi4yOGwtMTAuMTUtMzZsLTEuMTUtNi40OGwxLjA0LTQuMjNsMi4xMy0zLjU3bDMuNjEtMi4zbDEzLjY4LTQuODFsMTMuODQtNC4xNWw1LjUzLTAuMjFsMi42OCwwLjVMMTQ2LjIsMTY1LjAyeiIvPg0NCgkJPGc+DQ0KCQkJPHBhdGggY2xhc3M9InN0MiIgZD0iTTE2NC42NSwxNjEuMDVsMi40Ny0xLjMybDIuODYsMC4yMmwyLjAzLDEuNTlsMC42NiwyLjY0bDMwLjg1LDE3MS40MmwtMS44NywxLjg3bC0yLjY0LDAuNjZsLTMuMDgtMS4xDQ0KCQkJCWwtMi40Ny0yLjQybC0xLjE1LTIuNzVsLTE1LjI0LTc2LjA3bC04Ljg4LTM3LjQ4bC05LjYtMzcuNTRsLTAuNDQtNC41NmwwLjQ0LTIuOTFMMTY0LjY1LDE2MS4wNXoiLz4NDQoJCQk8cGF0aCBjbGFzcz0ic3Q0IiBkPSJNMTY2LjY0LDEzOC43NGwtMi4zNC00LjlsLTAuNzgtMC4xNWwtMS4yMSwwLjI2bC0xLjU1LDAuNTVsLTEuNzMsMC44OGwtMS41NywwLjg5bC0xLjMzLDAuOTJsLTAuOTEsMC43NA0NCgkJCQlsLTAuODksMC43N2wtMC44MiwwLjg4bC0wLjMyLDAuNTlsMC41NSw1LjE5bDEuNDEsNC42MmwwLjc3LDEuNzVsMC41LDAuODFsMC43OCwwLjY0bDAuNTgsMC4wOWwxLjIxLTAuMzhsMS40NS0wLjcxbDIuMDgtMS4xOA0NCgkJCQlsMi4wMS0xLjI4bDEuOTEtMS4zMmwwLjkxLTAuNzRsMC41OS0wLjY1bDAuMzMtMC41N2wwLjE4LTAuNjhsMC4wNi0wLjYzTDE2Ni42NCwxMzguNzR6IE0xNTYuODUsMTQ1Ljg0bC0wLjEyLTAuNDkNDQoJCQkJYzAuMDcsMC4yOCwwLjI1LDAuNjUsMC40NCwxLjAxTDE1Ni44NSwxNDUuODR6IE0xNjIuMzMsMTM1LjhsLTIuMzktMC4xN2wtNC4yNiwzLjQyYzAsMCwwLjg3LDUuNTMsMS4wNiw2LjMxbC0wLjg5LTMuNTgNDQoJCQkJbC0wLjItMS4yOWwwLjAzLTEuNDRsMC4zOS0wLjYxbDAuNzEtMC43bDAuOTUtMC44MmwxLjAxLTAuNjdsMS4yLTAuNjJsMS4xOC0wLjQzbDAuNzEtMC4wOWwwLjMzLDAuMTZsMC4yOSwwLjY2TDE2Mi4zMywxMzUuOHoNDQoJCQkJIE0xNjYuMDIsMTQ3LjJsLTAuMzIsMC4zNGwtMC44MSwwLjUyYzAuMzUtMC42OCwwLjg0LTEuNjUsMS4wNy0yLjEzbDAuMTgsMC45N0wxNjYuMDIsMTQ3LjJ6Ii8+DQ0KCQk8L2c+DQ0KCQk8Zz4NDQoJCQk8cGF0aCBjbGFzcz0ic3QyIiBkPSJNOTUuMTUsMzUzLjljLTAuMi0wLjAyLTAuMzktMC4wNS0wLjU5LTAuMDZjLTIuMDQtMC4wOC00LjA3LTAuMTgtNi4xMS0wLjI0Yy0xLjA2LTAuMDMtMi4wOS0wLjI0LTMuMTMtMC4zNg0NCgkJCQljLTAuMDEsMC0wLjAyLTAuMDEtMC4wMy0wLjAxYy0wLjY2LTAuMDEtMS4yMi0wLjI3LTEuNzQtMC42NmMtMC42Mi0wLjQ2LTEuMjktMC44Ny0xLjkzLTEuMzFjLTAuMTItMC4wOC0wLjIzLTAuMi0wLjMxLTAuMzINDQoJCQkJYy0wLjQ2LTAuNzUtMC45MS0xLjUxLTEuMzYtMi4yN2MtMC4wOC0wLjEzLTAuMTQtMC4yNy0wLjE4LTAuNDFjLTEuOTktNS45LTMuOTgtMTEuOC01Ljk3LTE3LjdjLTAuMDUtMC4xNC0wLjA3LTAuMy0wLjA2LTAuNDQNDQoJCQkJYzAuMDUtMS4wMiwwLjExLTIuMDQsMC4xNi0zLjA3YzAuMDEtMC4xNCwwLjA2LTAuMjIsMC4xOS0wLjI4YzAuNzMtMC4zOCwxLjQ3LTAuNzYsMi4yLTEuMTRjMC4xMS0wLjA2LDAuMjEtMC4wNywwLjMzLTAuMDUNDQoJCQkJYzAuNjgsMC4xLDEuMzcsMC4xOSwyLjA2LDAuMjljMC4xLDAuMDEsMC4yLDAuMDUsMC4yOSwwLjFjMC41NiwwLjMyLDEuMTEsMC42NSwxLjY4LDAuOTdjMC4xMywwLjA4LDAuMjEsMC4xOCwwLjI4LDAuMzENDQoJCQkJYzEuMDksMS45MywxLjk4LDMuOTcsMi45Miw1Ljk4YzEuMzEsMi44MiwyLjYyLDUuNjQsMy45Miw4LjQ2YzAuMDcsMC4xNiwwLjE3LDAuMjksMC4zMSwwLjRjMC42MSwwLjQ4LDEuMjEsMC45OCwxLjgyLDEuNDYNDQoJCQkJYzAuMTEsMC4wOCwwLjI0LDAuMTUsMC4zOCwwLjE4YzAuNzQsMC4xNCwxLjQ5LDAuMjcsMi4yNCwwLjM5YzAuMTUsMC4wMiwwLjMsMC4wMiwwLjQ1LDBjMC43Mi0wLjEyLDEuNDQtMC4yNSwyLjE2LTAuMzcNDQoJCQkJYzAuMjUtMC4wNCwwLjQ4LTAuMTEsMC43LTAuMjJjMS4zMi0wLjY1LDIuNjQtMS4yOSwzLjk2LTEuOTRjMC4xNC0wLjA3LDAuMjctMC4xNCwwLjM5LTAuMjNjMS4yNC0wLjgzLDIuNDctMS42NiwzLjcxLTIuNDkNDQoJCQkJYzAuMTItMC4wOCwwLjI0LTAuMTksMC4zNC0wLjNjMC44OC0xLjA0LDEuNzYtMi4wOSwyLjYzLTMuMTRjMC4wOS0wLjExLDAuMTYtMC4yMywwLjIxLTAuMzdjMC40NS0xLjMyLDAuOTEtMi42NCwxLjM0LTMuOTYNDQoJCQkJYzAuMDgtMC4yNiwwLjA5LTAuNTQsMC4xMS0wLjgxYzAuMTEtMS42OCwwLjIxLTMuMzcsMC4zMS01LjA1YzAuMDMtMC41NiwwLjA5LTEuMTIsMC4xLTEuNjdjMC0wLjMtMC4wOC0wLjYtMC4xMy0wLjkNDQoJCQkJYy0wLjE2LTAuOTEtMC4zMi0xLjgxLTAuNDgtMi43MmMtMC4yLTEuMTMtMC40LTIuMjYtMC42LTMuMzljLTAuMTktMS4wOC0wLjM4LTIuMTctMC41Ny0zLjI1Yy0wLjItMS4xMi0wLjQtMi4yNC0wLjU5LTMuMzYNDQoJCQkJYy0wLjE5LTEuMDgtMC4zOC0yLjE3LTAuNTctMy4yNWMtMC4yLTEuMTItMC40LTIuMjQtMC41OS0zLjM2Yy0wLjA3LTAuNDQtMC4xMi0wLjg4LTAuMTktMS4zMmMtMC4wNi0wLjM1LTAuMTQtMC42OS0wLjIyLTEuMDMNDQoJCQkJYy0wLjMxLTEuMzItMC42My0yLjY0LTAuOTUtMy45NWMtMC4zLTEuMjctMC42MS0yLjU0LTAuOTEtMy44MmMtMC4zNi0xLjUzLTAuNzItMy4wNi0xLjA4LTQuNmMtMC4zMy0xLjQxLTAuNjctMi44Mi0xLTQuMjMNDQoJCQkJYy0wLjMzLTEuNDEtMC42Ny0yLjgyLTEtNC4yM2MtMC4zMy0xLjQtMC42Ni0yLjgtMC45OS00LjIxYy0wLjMzLTEuNDEtMC42Ny0yLjgyLTEtNC4yM2MtMC4zMy0xLjQtMC42Ni0yLjgtMC45OS00LjIxDQ0KCQkJCWMtMC4zMy0xLjQxLTAuNjctMi44Mi0xLTQuMjNjLTAuMzMtMS40LTAuNjYtMi44MS0xLTQuMjFjLTAuMjQtMS4wMS0wLjQ0LTIuMDQtMC43Ny0zLjAzYy0wLjg0LTIuNTUtMy4yMy00LjMyLTUuOTEtNC40NQ0NCgkJCQljLTAuOTUtMC4wNS0xLjg3LDAuMDktMi43NiwwLjQ1Yy0wLjM2LDAuMTUtMC43MywwLjI1LTEuMTEsMC4zNmMtMC43MiwwLjIxLTEuNDMsMC40NS0yLjE0LDAuNjkNDQoJCQkJYy0zLjQ4LDEuMTctNi45NSwyLjM0LTEwLjQzLDMuNTFjLTAuMSwwLjAzLTAuMiwwLjA2LTAuMywwLjA3Yy0yLjUxLDAuMjYtNS4wMiwwLjUyLTcuNTMsMC43N2MtMC4xNCwwLjAxLTAuMjgsMC4wMS0wLjQyLTAuMDINDQoJCQkJYy0xLjIyLTAuMi0yLjQ1LTAuNDEtMy42Ny0wLjYzYy0wLjE0LTAuMDItMC4yNy0wLjA4LTAuMzktMC4xNWMtMS4xNC0wLjcxLTIuMjctMS40Mi0zLjQtMi4xM2MtMC4xMS0wLjA3LTAuMjEtMC4xNi0wLjMtMC4yNg0NCgkJCQljLTAuOTMtMS4wNy0xLjg1LTIuMTMtMi43OC0zLjJjLTAuMDktMC4xMS0wLjE3LTAuMjMtMC4yNC0wLjM1Yy0wLjc0LTEuMzQtMS40OC0yLjY3LTIuMi00LjAyYy0wLjMxLTAuNTktMC41Ni0xLjIyLTAuODMtMS44NA0NCgkJCQljLTAuMzMtMC43NS0wLjY0LTEuNS0wLjk4LTIuMjRjLTAuMjItMC40OC0wLjM3LTAuOTctMC40Ny0xLjQ5Yy0wLjA3LTAuMzYtMC4xNy0wLjcyLTAuMjQtMS4wOGMtMC4wNi0wLjMzLTAuMS0wLjY3LTAuMTQtMS4wMQ0NCgkJCQljLTAuMTYtMS4xNy0wLjMyLTIuMzUtMC40OC0zLjUzYy0wLjIxLTEuNTEtMC40Mi0zLjAzLTAuNjMtNC41NGMtMC4xNi0xLjE1LTAuMzEtMi4yOS0wLjQ3LTMuNDRjLTAuMjEtMS41NC0wLjQzLTMuMDgtMC42NC00LjYyDQ0KCQkJCWMtMC4xNS0xLjA5LTAuMy0yLjE4LTAuNDUtMy4yN2MtMC4yMi0xLjYxLTAuNDQtMy4yMS0wLjY2LTQuODJjLTAuMTYtMS4xNy0wLjMyLTIuMzMtMC40OC0zLjVjLTAuMjEtMS41Mi0wLjQyLTMuMDQtMC42My00LjU3DQ0KCQkJCWMtMC4xNS0xLjEtMC4zLTIuMi0wLjQ1LTMuM2MtMC4xNS0xLjEzLTAuMjgtMi4yNi0wLjQ3LTMuMzhjLTAuMTItMC43NC0wLjMxLTEuNDYtMC40OC0yLjE5Yy0wLjA0LTAuMTctMC4wMS0wLjM3LTAuMTgtMC40OQ0NCgkJCQljLTAuMDMtMC4zNi0wLjA2LTAuNzItMC4wOS0xLjA4YzAuMDEtMC4wOSwwLjAzLTAuMTksMC4wMy0wLjI4Yy0wLjAxLTAuNDItMC4wMi0wLjgzLTAuMDUtMS4yNWMtMC4wNy0xLjE5LTAuMTQtMi4zOS0wLjIxLTMuNTgNDQoJCQkJYy0wLjAxLTAuMjItMC4wMi0wLjQ0LDAtMC42NWMwLjEzLTEuMjEsMC4yNy0yLjQxLDAuNDItMy42MmMwLjAyLTAuMTUsMC4wNi0wLjMsMC4xMy0wLjQzYzAuNTEtMS4wNCwxLjAyLTIuMDgsMS41NC0zLjExDQ0KCQkJCWMwLjA3LTAuMTUsMC4xOC0wLjI5LDAuMy0wLjQxYzAuNzQtMC43MiwxLjQ5LTEuNDMsMi4yNC0yLjE0YzAuMS0wLjEsMC4yMS0wLjE5LDAuMzMtMC4yN2MxLjM1LTAuOTUsMi43MS0xLjg5LDQuMDYtMi44Mw0NCgkJCQljMC4xMS0wLjA4LDAuMjItMC4xNSwwLjM0LTAuMjFjMS40Mi0wLjc2LDIuODQtMS41Miw0LjI3LTIuMjhjMC4xMy0wLjA3LDAuMjYtMC4xMiwwLjM5LTAuMTdjMS4wNS0wLjQsMi4xMS0wLjgxLDMuMTYtMS4yMQ0NCgkJCQljMC42OC0wLjI2LDEuMzYtMC41NCwyLjA1LTAuNzZjMS4yLTAuMzgsMi40Mi0wLjcyLDMuNjMtMS4wN2MwLjU0LTAuMTYsMS4wNy0wLjMzLDEuNjItMC40M2MxLjA3LTAuMjEsMi4xNS0wLjM3LDMuMjItMC41Nw0NCgkJCQljMS4xNC0wLjIxLDIuMjgtMC40LDMuNDMtMC41M2MxLjg3LTAuMjIsMy43NS0wLjQ2LDUuNjItMC42OGMwLjM4LTAuMDQsMC43OC0wLjA3LDEuMTYtMC4wNmMxLjEyLDAuMDIsMi4xNiwwLjMyLDMuMDgsMC45Ng0NCgkJCQljMC42OCwwLjQ3LDEuMzEsMS4wMSwxLjk1LDEuNTJjMC4xMSwwLjA5LDAuMjEsMC4yMiwwLjI3LDAuMzVjMC4zNywwLjc3LDAuNzQsMS41NCwxLjEsMi4zMWMwLjA5LDAuMTksMC4xNSwwLjM5LDAuMiwwLjU5DQ0KCQkJCWMwLjI4LDEuMTYsMC41NSwyLjMzLDAuODIsMy40OWMwLjQyLDEuNzksMC44NSwzLjU4LDEuMjcsNS4zN2MwLjM5LDEuNjYsMC43OCwzLjMyLDEuMTgsNC45OGMwLjM0LDEuNDQsMC42OCwyLjg4LDEuMDEsNC4zMg0NCgkJCQljMC4wMiwwLjExLDAuMDMsMC4yMywwLjAxLDAuMzNjLTAuMTUsMS4wMS0wLjMsMi4wMy0wLjQ1LDMuMDRjLTAuMDIsMC4xNy0wLjEsMC4yNi0wLjI2LDAuMzJjLTAuNzQsMC4yOS0xLjQ4LDAuNTktMi4yMiwwLjg5DQ0KCQkJCWMtMC4xMywwLjA1LTAuMjUsMC4wNi0wLjM4LDAuMDNjLTAuNjUtMC4xNi0xLjMxLTAuMzEtMS45Ni0wLjQ3Yy0wLjEyLTAuMDMtMC4yNS0wLjA5LTAuMzYtMC4xNmMtMC41LTAuMzUtMC45OS0wLjcxLTEuNDktMS4wNQ0NCgkJCQljLTAuMTUtMC4xLTAuMjQtMC4yMy0wLjMxLTAuMzljLTAuNzctMS43My0xLjM5LTMuNTMtMi4wMi01LjMxYy0xLjAzLTIuOTItMi4wNy01Ljg1LTMuMTEtOC43NmMtMC41My0xLjQ5LTIuMTktMi43MS00LjE5LTIuNDQNDQoJCQkJYy0wLjQyLDAuMDYtMC44NCwwLjEzLTEuMjYsMC4yMWMtMC41LDAuMDktMS4wMiwwLjE1LTEuNSwwLjMxYy0yLjU3LDAuODMtNS4xMywxLjY5LTcuNjksMi41NWMtMC4yNSwwLjA4LTAuNTEsMC4xNy0wLjczLDAuMw0NCgkJCQljLTEuMDEsMC41OC0yLjAxLDEuMTgtMy4wMiwxLjc3Yy0wLjExLDAuMDctMC4yMiwwLjE1LTAuMzEsMC4yNWMtMC44LDAuODQtMS41OSwxLjY4LTIuMzgsMi41M2MtMC4wOSwwLjA5LTAuMTYsMC4yMi0wLjE5LDAuMzQNDQoJCQkJYy0wLjMsMS4xMS0wLjU5LDIuMjItMC44OCwzLjMzYy0wLjAzLDAuMTQtMC4wNSwwLjI4LTAuMDQsMC40MmMwLjA1LDEuMDcsMC4xMSwyLjE0LDAuMTcsMy4yMWMwLjAzLDAuNTksMC4wMywxLjE4LDAuMjksMS43Mw0NCgkJCQljMC4wMiwwLjA1LDAuMDMsMC4xNSwwLDAuMThjLTAuMDgsMC4wOS0wLjA1LDAuMTgtMC4wNCwwLjI3YzAuMTcsMS4yLDAuMzMsMi40MSwwLjUsMy42MWMwLjE1LDEuMDQsMC4yOSwyLjA5LDAuNDQsMy4xMw0NCgkJCQljMC4yMiwxLjU0LDAuNDMsMy4wOCwwLjY1LDQuNjJjMC4xNSwxLjA1LDAuMjksMi4xLDAuNDQsMy4xNmMwLjIyLDEuNTQsMC40MywzLjA4LDAuNjUsNC42MmMwLjE1LDEuMDYsMC4yOSwyLjEyLDAuNDQsMy4xOQ0NCgkJCQljMC4yNSwxLjc3LDAuNSwzLjUzLDAuNzUsNS4zYzAuMiwxLjQ3LDAuNCwyLjkzLDAuNjEsNC40YzAuMjYsMS45LDAuNTMsMy44LDAuOCw1LjY5YzAuMDYsMC40MywwLjEsMC44NywwLjIzLDEuMjkNDQoJCQkJYzAuMzcsMS4yMSwwLjc4LDIuNDIsMS4xOCwzLjYyYzAuMDQsMC4xMiwwLjExLDAuMjQsMC4xOCwwLjM1YzAuNzEsMC45MSwxLjQyLDEuODIsMi4xNCwyLjczYzAuMDcsMC4wOSwwLjE3LDAuMTgsMC4yNywwLjI1DQ0KCQkJCWMwLjc2LDAuNDgsMS41MiwwLjk2LDIuMjksMS40M2MwLjEsMC4wNiwwLjIzLDAuMSwwLjM0LDAuMTFjMC41OCwwLjA1LDEuMTUsMC4wOCwxLjczLDAuMTNjMC41MywwLjA0LDEuMDYsMC4wNywxLjU3LTAuMTINDQoJCQkJYzAuMTktMC4wNywwLjQtMC4xMSwwLjYtMC4xNmMwLjYxLTAuMTcsMS4yNC0wLjI5LDEuODQtMC41MWMyLjA5LTAuNzUsNC4xNi0xLjU0LDYuMjQtMi4zMWMwLjE0LTAuMDUsMC4yOC0wLjEyLDAuNDEtMC4xOQ0NCgkJCQljMS44My0xLjA0LDMuNjYtMi4wOCw1LjQ5LTMuMTFjMC4xNS0wLjA4LDAuMy0wLjE2LDAuNDYtMC4yM2MxLjQ3LTAuNjcsMi45NC0xLjMyLDQuNC0yYzEuMzctMC42NCwyLjgyLTAuODMsNC4zLTAuNzINDQoJCQkJYzEuMjMsMC4wOSwyLjQ1LDAuMjgsMy42NywwLjQ1YzEuOTksMC4yOCwzLjcxLDEuMTIsNS4xNywyLjQ5YzAuMzMsMC4zLDAuNjYsMC42LDAuOTgsMC45MWMwLjExLDAuMTEsMC4yMiwwLjI0LDAuMjksMC4zOA0NCgkJCQljMS4wOSwyLjA1LDIuMTgsNC4xLDMuMjYsNi4xNmMwLjA5LDAuMTYsMC4xNSwwLjM1LDAuMTksMC41M2MwLjI2LDEuMTcsMC41MiwyLjMzLDAuNzcsMy41YzAuMTQsMC42NiwwLjI5LDEuMzEsMC40MSwxLjk4DQ0KCQkJCWMwLjE5LDEuMDgsMC4zNSwyLjE3LDAuNTIsMy4yNmMwLjIxLDEuNDEsMC40MiwyLjgxLDAuNjEsNC4yMmMwLjE4LDEuMzQsMC4zNSwyLjY5LDAuNTIsNC4wNGMwLjI0LDEuODksMC40OSwzLjc4LDAuNzMsNS42Nw0NCgkJCQljMC4xNiwxLjI3LDAuMzMsMi41NCwwLjQ5LDMuODFjMC4yNCwxLjg0LDAuNDgsMy42OSwwLjcyLDUuNTNjMC4xNywxLjMxLDAuMzQsMi42MiwwLjUsMy45MmMwLjI0LDEuODgsMC40OSwzLjc2LDAuNzMsNS42NA0NCgkJCQljMC4xMywwLjk3LDAuMjYsMS45NCwwLjM4LDIuOTFjMC4wOCwwLjYsMC4xNSwxLjIsMC4yMywxLjgxYzAuMDEsMC4wNywwLjAyLDAuMi0wLjAxLDAuMjFjLTAuMiwwLjEtMC4xMiwwLjI2LTAuMSwwLjQNDQoJCQkJYzAuMTUsMS4xNiwwLjMsMi4zMSwwLjQ1LDMuNDdjMC4xNiwxLjI0LDAuMzIsMi40OCwwLjQ4LDMuNzNjMC4yNCwxLjg0LDAuNDgsMy42OSwwLjcyLDUuNTNjMC4xNywxLjMxLDAuMzQsMi42MiwwLjUsMy45Mg0NCgkJCQljMC4yNCwxLjg4LDAuNDksMy43NiwwLjczLDUuNjRjMC4xNywxLjI4LDAuMzMsMi41NiwwLjUsMy44NGMwLjIzLDEuNzYsMC40NiwzLjUyLDAuNjksNS4yOGMwLjE4LDEuMzgsMC4zNSwyLjc3LDAuNTMsNC4xNQ0NCgkJCQljMC4yNiwyLjA3LDAuNTIsNC4xNCwwLjc4LDYuMjFjMC4wMSwwLjEsMC4wNSwwLjIsMC4wNywwLjNjMC4wNCwwLjQ0LDAuMDcsMC44NywwLjExLDEuMzFjLTAuMDMsMC4xNS0wLjA2LDAuMzEtMC4wOSwwLjQ2DQ0KCQkJCWMtMC4yOCwxLjYyLTAuNTYsMy4yNS0wLjg1LDQuODdjLTAuMDMsMC4xNS0wLjA3LDAuMjktMC4xNCwwLjQzYy0wLjYyLDEuMTYtMS4yNSwyLjMyLTEuODcsMy40OGMtMC4wOCwwLjE1LTAuMTgsMC4yNi0wLjMxLDAuMzYNDQoJCQkJYy0wLjg2LDAuNjItMS43MiwxLjI1LTIuNTgsMS44N2MtMC4xMSwwLjA4LTAuMjQsMC4xNi0wLjM2LDAuMjJjLTEuNjMsMC44OS0zLjI2LDEuNzktNC44OSwyLjY4Yy0wLjE3LDAuMDktMC4zNiwwLjE3LTAuNTUsMC4yNA0NCgkJCQljLTEuODIsMC42Mi0zLjY0LDEuMjMtNS40NiwxLjg1Yy0wLjI4LDAuMS0wLjU3LDAuMTUtMC44NiwwLjE4Yy0xLjU0LDAuMTUtMy4wOCwwLjMtNC42MiwwLjQ2Yy0wLjEyLDAuMDEtMC4yNCwwLjA1LTAuMzYsMC4wNw0NCgkJCQlDOTYuODksMzUzLjc2LDk2LjAyLDM1My44Myw5NS4xNSwzNTMuOXoiLz4NDQoJCTwvZz4NDQoJPC9nPg0NCjwvZz4NDQo8L3N2Zz4NDQo=');
		
				// Montering
				seiIkonLink.appendChild(seiIkonCont);
				seiIkonLink.appendChild(seiIkonLabel);
				seiIkon.appendChild(seiIkonLink);
				// Legg ut i sida
				let m = document.getElementById('menu');
				if (m != null){
					m.appendChild(seiIkon);
				}

}
		
			// Sjekker at elementet ikke allerede er lagt til
			if (document.getElementById('eksamenskoden') == null){
				// Elementer og oppsett etter Canvas sin standard som kan bli endret uten forvarsel!
				var eksIkon = document.createElement('li');
				eksIkon.id = 'eksamenskoden';
				eksIkon.className = 'menu-item ic-app-header__menu-list-item';
				eksIkon.style.zoom = '.8';
				//eksIkon.style.marginTop = '1rem';
				//eksIkon.style.paddingTop = '1rem';
				let eksIkonLink =  document.createElement('a');
				eksIkonLink.href = 'http://eksamenskoden.no';
				eksIkonLink.target = '_blank';
				eksIkonLink.className = 'ic-app-header__menu-list-link';
				let eksIkonLabel =  document.createElement('div');
				eksIkonLabel.className = 'menu-item__text';
				let eksIkonLabelSpan = document.createElement('span');
				eksIkonLabelSpan.innerHTML = ((langStartsWithEn.test(document.documentElement.lang) ? 'Exam Tips' : 'Eksamens&shy;tips'));
				eksIkonLabel.appendChild(eksIkonLabelSpan);
				let eksIkonCont =  document.createElement('div');
				eksIkonCont.className = 'menu-item-icon-container';
		eksIkonCont.innerHTML = atob('PD94bWwgdmVyc2lvbj0iMS4wIiBlbmNvZGluZz0idXRmLTgiPz4KPHN2ZyB2ZXJzaW9uPSIxLjEiIGlkPSJla3NhbWVuc0tvZGVuSWtvbiIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIiB4bWxuczp4bGluaz0iaHR0cDovL3d3dy53My5vcmcvMTk5OS94bGluayIgeD0iMHB4IiB5PSIwcHgiCgkgdmlld0JveD0iMCAwIDQyNS4yIDQyNS4yIiBzdHlsZT0iZW5hYmxlLWJhY2tncm91bmQ6bmV3IDAgMCA0MjUuMiA0MjUuMjsiIHhtbDpzcGFjZT0icHJlc2VydmUiPgo8c3R5bGUgdHlwZT0idGV4dC9jc3MiPgoJLmZhcmdle2ZpbGw6I0ZGRkZGRjt9Cjwvc3R5bGU+CjxnPgoJPHBhdGggY2xhc3M9ImZhcmdlIiBkPSJNNTcuMjMsMTU1LjAxYzAsMi44LTAuMDYsNC43NC0wLjE3LDUuODFsLTAuMTcsMi4yNmMtMS42LDMzLjkxLTIuNDEsNjcuNzctMi40MSwxMDEuNTgKCQljMC42OSwwLjY1LDQuOTgsMC45NywxMi44OCwwLjk3aDUuODRjMCwyLjI2LTAuMjksMy4zOS0wLjg2LDMuMzloLTIuMjNoLTcuMDRjLTguMDIsMC0xMi4zNy0wLjMyLTEzLjA2LTAuOTcKCQljMC0zMy44LDAuOC02Ny42NiwyLjQxLTEwMS41OGwwLjE3LTIuMjZsMC4xNy05LjJINTcuMjN6IE03Ny4xNiwyMjQuNjFjMC0wLjY1LDAuMDMtMS42MSwwLjA5LTIuOTFjMC4wNi0xLjI5LDAuMDktMi4yNiwwLjA5LTIuOTEKCQl2LTAuNjVjMC4yMywwLjExLDAuNTQsMC4xOSwwLjk0LDAuMjRjMC40LDAuMDUsMC43NywwLjExLDEuMTIsMC4xNmMwLjM0LDAuMDYsMC41MiwwLjE0LDAuNTIsMC4yNAoJCWM0LjEyLDYuMDMsOC44NywxMy42NSwxNC4yNiwyMi44NWM1LjM4LDkuMiw4LjUzLDE0LjUxLDkuNDUsMTUuOTFjMS45NSwzLjIzLDMuNzIsNS44MSw1LjMzLDcuNzVjMC41NywwLjY1LDMuNzgsMC45Nyw5LjYyLDAuOTcKCQljMi42MywwLDUuNzgtMC4xMSw5LjQ1LTAuMzJjMC41NywwLjg2LDAuODYsMS42MiwwLjg2LDIuMjZjMCwwLjk3LTQuODcsMS40NS0xNC42LDEuNDVjLTUuOTYsMC05LjIyLTAuMzItOS43OS0wLjk3CgkJYy0xLjI2LTEuNTEtMi45OC00LjA5LTUuMTUtNy43NWMtMS4zNy0yLjI2LTQuNS03LjUzLTkuMzYtMTUuODNDODUuMDksMjM2LjgzLDgwLjgyLDIyOS45OSw3Ny4xNiwyMjQuNjF6IE0xMDUuNjgsMTU4LjQKCQljLTIuODYsMy43Ny03LjIyLDEwLjM0LTEzLjA2LDE5LjdjLTUuODQsOS4zNy0xMC43NywxNi4zNy0xNC43NywyMC45OXYtMi40MmMyLjc1LTMuNjYsNi44MS05Ljc0LDEyLjItMTguMjUKCQljNS4zOC04LjUsOS45MS0xNS4wNywxMy41Ny0xOS43QzEwNC4wNywxNTguNTEsMTA0Ljc2LDE1OC40LDEwNS42OCwxNTguNHoiLz4KCTxwYXRoIGNsYXNzPSJmYXJnZSIgZD0iTTEzOS4wMSwxNzguNDJjLTMuNDQsMi45MS02LjUzLDYuODktOS4yOCwxMS45NWMtMy42Nyw2Ljg5LTUuNSwxMy44NC01LjUsMjAuODNjMCwwLjMyLDAuMTcsMi4xLDAuNTIsNS4zMwoJCWMyLjE3LDE0Ljc1LDkuMjIsMjUuNjgsMjEuMTMsMzIuNzhjOC41OSw1LjA2LDE2Ljg5LDcuNTksMjQuOTEsNy41OWM2LjY0LDAsMTIuNzctMS44MywxOC4zOC01LjQ5CgkJYy02Ljc2LDUuODEtMTQuMzcsOC43Mi0yMi44NSw4LjcyYy03LjksMC0xNi4xNS0yLjUzLTI0Ljc0LTcuNTljLTExLjkxLTcuMTEtMTguOTUtMTcuOTgtMjEuMTMtMzIuNjIKCQljLTAuMzQtMy4yMy0wLjUyLTUuMDEtMC41Mi01LjMzYzAtNy4yMSwxLjc4LTE0LjE2LDUuMzMtMjAuODNjMi41Mi00Ljg1LDUuNzMtOC44OCw5LjYyLTEyLjExCgkJQzEzNi43MSwxODAuMTUsMTM4LjA5LDE3OS4wNywxMzkuMDEsMTc4LjQyeiBNMTg1LjU2LDIxMS42OWMwLjIzLDEuMjksMC4zNCwyLjM3LDAuMzQsMy4yM2MwLDIuMTYtMC41Miw0LjktMS41NSw4LjI0CgkJYy0wLjIzLDAuNDMtMC42LDEuMS0xLjEyLDIuMDJjLTAuNTIsMC45Mi0wLjg5LDEuNTktMS4xMiwyLjAyYy0yLjY0LDQuNDItNS41Niw3LjQzLTguNzYsOS4wNGMxLjYtMS43MiwzLjAzLTMuNjYsNC4yOS01LjgxCgkJYzAuMjMtMC40MywwLjYzLTEuMDcsMS4yLTEuOTRjMC41Ny0wLjg2LDAuOTctMS41MSwxLjItMS45NGMwLjkyLTIuOTEsMS4zNy01LjcxLDEuMzctOC40YzAtMC43NS0wLjEyLTEuNzgtMC4zNC0zLjA3CgkJYy0xLjI2LTguMDctNS4xLTE0LjMyLTExLjUxLTE4LjczYy00LjM1LTIuOC04LjU5LTQuMi0xMi43MS00LjJjLTIuOTgsMC01LjU2LDAuNTQtNy43MywxLjYxYzMuMjEtMy4zNCw3LjIyLTUuMDEsMTIuMDMtNS4wMQoJCWM0LjAxLDAsOC4zLDEuNDUsMTIuODgsNC4zNkMxODAuNDcsMTk3LjUzLDE4NC4zLDIwMy43MiwxODUuNTYsMjExLjY5eiIvPgoJPHBhdGggY2xhc3M9ImZhcmdlIiBkPSJNMjE5LjI0LDI2My4wNGMwLDEuMDgsMS4xNCwxLjYyLDMuNDQsMS42MmMwLjU3LDAsMS42Ni0wLjA2LDMuMjYtMC4xNmMxLjYtMC4xMSwyLjgtMC4xNiwzLjYxLTAuMTYKCQljMS45NSwwLDQuMjktMC4zMiw3LjA0LTAuOTdjNy42Ny0xLjgzLDEzLjc0LTQuMDksMTguMjEtNi43OGMwLjM0LTAuMjEsMC44My0wLjU0LDEuNDYtMC45N2MwLjYzLTAuNDMsMS4xMi0wLjc1LDEuNDYtMC45NwoJCWMtMS40OSwxLjI5LTMuOTUsMy4wMi03LjM5LDUuMTdjLTQuNTgsMi44LTEwLjYsNS4wNi0xOC4wNCw2Ljc4Yy0zLjIxLDAuNzUtNS41NiwxLjEzLTcuMDQsMS4xM2MtMC44LDAtMi4wMSwwLjA1LTMuNjEsMC4xNgoJCWMtMS42LDAuMTEtMi42OSwwLjE2LTMuMjYsMC4xNmMtMi40MSwwLTMuNjEtMC41OS0zLjYxLTEuNzh2LTUuMTd2LTguMDd2LTMuNzFjMC0wLjc1LTAuMDMtMS45OS0wLjA5LTMuNzEKCQljLTAuMDYtMS43Mi0wLjA5LTIuOTYtMC4wOS0zLjcxYy0wLjEyLTMuNDQtMC4yOS04Ljg1LTAuNTItMTYuMjNjLTAuMjMtNy4zNy0wLjQtMTQuMjQtMC41Mi0yMC41OWMtMC4xMS02LjM1LTAuMTctMTItMC4xNy0xNi45NgoJCXYtMTEuM2MwLTQuMiwwLjEyLTkuNTMsMC4zNC0xNS45OWMwLTAuNTQsMS40My0wLjgxLDQuMjktMC44MWMtMC4xMiwyLjgtMC4xNyw3LjMyLTAuMTcsMTMuNTd2MTEuM2MwLDQuOTUsMC4wNiwxMC41OCwwLjE3LDE2Ljg4CgkJYzAuMTEsNi4zLDAuMjgsMTMuMTQsMC41MiwyMC41MWMwLjIzLDcuMzgsMC40LDEyLjc5LDAuNTIsMTYuMjNjMCwwLjg2LDAuMDMsMi4xLDAuMDksMy43MWMwLjA2LDEuNjEsMC4wOSwyLjg1LDAuMDksMy43MXYzLjg4CgkJdjcuOTFWMjYzLjA0eiBNMjM3LjYyLDE4Mi40NmMwLTEuNjEsMC4wNi0yLjgsMC4xNy0zLjU1YzYuNzYsMCwxMi40NiwxLjcsMTcuMDksNS4wOWM0LjY0LDMuMzksNi45Niw3Ljk5LDYuOTYsMTMuODEKCQljMCwwLjMyLTAuMDMsMC44Ni0wLjA5LDEuNjFjLTAuMDYsMC43NS0wLjA5LDEuMjktMC4wOSwxLjYxYzAsOC40LTEuNTgsMTYuMS00LjcyLDIzLjA5Yy0zLjE1LDctNy4zLDEyLjM4LTEyLjQ2LDE2LjE1CgkJYy0wLjEyLDAuMjItMC40LDAuNDMtMC44NiwwLjY0Yy0wLjM0LDAuMjItMC42MywwLjM4LTAuODYsMC40OWM0LjQ3LTMuOTgsNy45Ni05LjIsMTAuNDgtMTUuNjdjMi41Mi02LjU2LDMuODQtMTMuNzMsMy45NS0yMS40OAoJCWMwLTAuMzIsMC4wMy0wLjgzLDAuMDktMS41M2MwLjA2LTAuNywwLjA5LTEuMjEsMC4wOS0xLjUzYzAtNS4wNi0xLjg2LTkuMjYtNS41OC0xMi42QzI0OC4wNywxODUuMjYsMjQzLjM0LDE4My4yMSwyMzcuNjIsMTgyLjQ2egoJCSIvPgoJPHBhdGggY2xhc3M9ImZhcmdlIiBkPSJNMjk0LjQ4LDE1Ny41OXYyNC43MWMwLDIuOC0wLjA5LDcuMDItMC4yNiwxMi42OGMtMC4xNyw1LjY1LTAuMjYsOS45My0wLjI2LDEyLjg0djEyLjc2CgkJYzAsMS4wOC0wLjE0LDYuMTEtMC40MywxNS4xYy0wLjI5LDguOTktMC40MywxNS4zNy0wLjQzLDE5LjE0YzAsMy44OCwwLjExLDUuOTIsMC4zNCw2LjE0YzEuNiwxLjUxLDE2LjYxLDIuMjYsNDUuMDEsMi4yNmg3LjU2CgkJaDIuNThoNC4yOWMxLjAzLDAsMy4wMy0wLjExLDYuMDEtMC4zMmMwLjQ2LTAuMTEsMS43Mi0wLjE2LDMuNzgtMC4xNmMtMC4yMywxLjYyLTAuNDYsMi41My0wLjY5LDIuNzUKCQljLTAuMjMsMC4yMS0wLjg2LDAuMzgtMS44OSwwLjQ4Yy0xLjAzLDAuMTEtMi4xOCwwLjE2LTMuNDQsMC4xNmMtMS4yNiwwLTIuMDEsMC0yLjIzLDBjLTIuOTgsMC4yMi00Ljk4LDAuMzItNi4wMSwwLjMyaC00LjEyCgkJaC0yLjc1SDMzNGMtMjguNDEsMC00My40MS0wLjc2LTQ1LjAxLTIuMjZjLTAuMjMtMC4yMS0wLjM0LTIuMjYtMC4zNC02LjE0YzAtMy43NywwLjE0LTEwLjE1LDAuNDMtMTkuMTQKCQljMC4yOC04Ljk5LDAuNDMtMTQuMDIsMC40My0xNS4xdi0xMi43NmMwLTIuOTEsMC4wOS03LjE2LDAuMjYtMTIuNzZjMC4xNy01LjYsMC4yNi05LjgsMC4yNi0xMi42di0yNi45NwoJCUMyOTAuMDIsMTU4LjE4LDI5MS41LDE1Ny44MSwyOTQuNDgsMTU3LjU5eiBNMzE1Ljc5LDIxNy4zNGMwLTAuNDMsMC4wMy0xLjA1LDAuMDktMS44NmMwLjA2LTAuODEsMC4wOS0xLjQzLDAuMDktMS44NgoJCWM1LjUsMC43NiwxNC4yNiwxLjEzLDI2LjI5LDEuMTNoMC41Mmg1LjY3aDAuNjloMC4zNHYwLjE2YzAsMS43Mi0wLjI5LDIuNTgtMC44NiwyLjU4Yy0wLjEyLDAtMC43NSwwLjA4LTEuODksMC4yNAoJCWMtMS4xNSwwLjE2LTEuNzgsMC4yNC0xLjg5LDAuMjRoLTAuNjloLTUuNjdoLTAuNTJjLTIuMjksMC01Ljk2LTAuMTEtMTEtMC4zMkMzMjEuOTEsMjE3LjQ1LDMxOC4xOSwyMTcuMzQsMzE1Ljc5LDIxNy4zNHoKCQkgTTMxNS45NiwxODAuMnYtMy4wN2MwLjY5LDAsMS44LTAuMDUsMy4zNS0wLjE2YzEuNTUtMC4xMSwyLjcyLTAuMTYsMy41Mi0wLjE2YzIuNjMsMCw2LjUsMC4wMywxMS42LDAuMDgKCQljNS4xLDAuMDYsOC44NSwwLjA4LDExLjI1LDAuMDhjMS4wMywwLDIuNTItMC4wMyw0LjQ3LTAuMDhjMS45NS0wLjA1LDMuMzgtMC4wOCw0LjI5LTAuMDhjMy41NSwwLDYuMjQtMC4wNSw4LjA3LTAuMTYKCQljLTAuMjMsMi4xNi0wLjg2LDMuMjMtMS44OSwzLjIzYy0wLjY5LDAtMi40NCwwLjA2LTUuMjQsMC4xNmMtMi44MSwwLjExLTQuNjEsMC4xNi01LjQxLDAuMTZjLTAuOTIsMC0yLjM1LDAuMDMtNC4yOSwwLjA4CgkJYy0xLjk1LDAuMDYtMy40NCwwLjA4LTQuNDcsMC4wOGMtMi40MSwwLTYuMTYtMC4wMi0xMS4yNS0wLjA4Yy01LjEtMC4wNS04Ljk2LTAuMDgtMTEuNi0wLjA4SDMxNS45NnoiLz4KCTxwYXRoIGNsYXNzPSJmYXJnZSIgZD0iTTE1OC45MywyOTAuMjljMC4zNCw2LjM1LDAuNTcsMTUuNjQsMC42OSwyNy44NmMwLjExLDEyLjIyLDAuMTcsMTkuNDEsMC4xNywyMS41NmMwLDAuNDMsMC4wMywxLjIxLDAuMDksMi4zNAoJCWMwLjA2LDEuMTMsMC4wOSwxLjk3LDAuMDksMi41YzAsMS44My0wLjA2LDQuNjYtMC4xNyw4LjQ4Yy0wLjEyLDMuODItMC4xNyw2LjctMC4xNyw4LjY0djMuMDdsMC4zNCw4LjI0CgkJYzAsMC40MywwLjkyLDAuNjUsMi43NSwwLjY1YzAuNjksMCwzLjQ0LTAuMTYsOC4yNS0wLjQ5aDAuNTJjMS4xNCwwLDIuNjMsMC4xMSw0LjQ3LDAuMzJjMCwwLjQzLTAuMDMsMC45Ny0wLjA5LDEuNjEKCQljLTAuMDYsMC42NS0wLjA5LDEuMDctMC4wOSwxLjI5YzAsMC40My0wLjYzLDAuNjUtMS44OSwwLjY1Yy0wLjU3LDAtMS42Ni0wLjA4LTMuMjYtMC4yNGMtMS42LTAuMTYtMi43NS0wLjI0LTMuNDQtMC4yNGgtMC41MgoJCWMtNC44MSwwLjMyLTcuNTYsMC40OC04LjI1LDAuNDhjLTEuOTUsMC0yLjkyLTAuMjItMi45Mi0wLjY1bC0wLjM0LTguNHYtMi45MWMwLTEuOTQsMC4wNi00Ljg1LDAuMTctOC43MgoJCWMwLjExLTMuODgsMC4xNy02LjczLDAuMTctOC41NmMwLTAuNTQtMC4wMy0xLjM0LTAuMDktMi40MmMtMC4wNi0xLjA4LTAuMDktMS44My0wLjA5LTIuMjZjMC0yLjE1LTAuMDYtOS4zNC0wLjE3LTIxLjU2CgkJYy0wLjEyLTEyLjIyLTAuMzQtMjEuNS0wLjY5LTI3Ljg2Yy0wLjM0LTUuNDktMC41Mi0xMC43Ni0wLjUyLTE1LjgzYzAtNi41NywwLjUyLTEwLjA3LDEuNTUtMTAuNWMwLjQ2LDAsMS40OSwwLjQ5LDMuMDksMS40NQoJCWMtMC4xMiwxLjA4LTAuMTcsMy4wMi0wLjE3LDUuODFDMTU4LjQyLDI3OS41OCwxNTguNTksMjg0LjgsMTU4LjkzLDI5MC4yOXogTTE3OS43MiwzMTMuNTVjLTAuMjMtNC4wOS0wLjQtNy4xMS0wLjUyLTkuMDQKCQljMC41NywwLjQzLDAuOTcsMC43OCwxLjIsMS4wNWMwLjIzLDAuMjcsMC40MywwLjU3LDAuNiwwLjg5YzAuMTcsMC4zMiwwLjQ5LDAuNzYsMC45NCwxLjI5YzAuMjMsMC4yMiwwLjU3LDAuNjUsMS4wMywxLjI5CgkJYzAuNDYsMC42NCwwLjg2LDEuMDgsMS4yLDEuMjljMS4wMywxLjE5LDIuNjYsMi43Nyw0LjksNC43NmMyLjIzLDEuOTksMy43NSwzLjQyLDQuNTUsNC4yOGMyLjA2LDEuOTQsNS4xOCw0Ljg3LDkuMzYsOC44CgkJYzQuMTgsMy45Myw3LjI0LDYuODYsOS4xOSw4LjhjMy4yMSwzLjIzLDguMzksNy44OSwxNS41NSwxMy45N2M3LjE2LDYuMDgsMTIuNCwxMC44NSwxNS43MiwxNC4yOWMyLjQxLDIuNjksNS44NCw1LjcxLDEwLjMxLDkuMDQKCQlsMC4zNCwwLjE2YzAuMjMsMC4xMSwwLjM0LDAuMjEsMC4zNCwwLjMybC0wLjUyLDEuOTRjLTAuMzQsMS4wNy0xLjIsMS42MS0yLjU4LDEuNjFjLTAuOCwwLTEuNDktMC4yMi0yLjA2LTAuNjQKCQljLTMuNzgtMi44LTcuMTYtNS44Ny0xMC4xNC05LjJjLTMuMzItMy40NS04LjU5LTguMjEtMTUuODEtMTQuMjljLTcuMjItNi4wOC0xMi40My0xMC43NC0xNS42My0xMy45NwoJCWMtMS45NS0yLjA1LTQuOTgtNC45OC05LjExLTguOGMtNC4xMi0zLjgyLTcuMjItNi43LTkuMjgtOC42NGMtMC44LTAuODYtMi4zNS0yLjMxLTQuNjQtNC4zNgoJCUMxODIuNDEsMzE2LjM1LDE4MC43NSwzMTQuNzMsMTc5LjcyLDMxMy41NXogTTI0Ni44OSwyODEuNTdjLTAuNDYsMi45MS0wLjk4LDcuNjctMS41NSwxNC4yOWMtMC41Nyw2LjYyLTEuMDksMTMuMTYtMS41NSwxOS42MgoJCWMtMC40Niw2LjQ2LTAuNzUsMTAuMTItMC44NiwxMC45OGMtMC4xMiwwLjc2LTAuMjYsMS44LTAuNDMsMy4xNWMtMC4xNywxLjM1LTAuMjYsMi4zNC0wLjI2LDIuOTljMCwxLjE5LTAuMDYsMi4wNS0wLjE3LDIuNTgKCQlsLTMuNjEtNC4zNnYtMC45N2MwLTAuNjUsMC4xNy0zLjA0LDAuNTItNy4xOWMwLjM0LTQuMTQsMC42OS04LjMyLDEuMDMtMTIuNTJzMC43MS04LjU4LDEuMTItMTMuMTZjMC40LTQuNTcsMC44LTguMjksMS4yLTExLjE0CgkJYzAuNC0yLjg1LDAuNzctNC4yOCwxLjEyLTQuMjhIMjQ2Ljg5eiIvPgo8L2c+CjxnPgoJPHBhdGggY2xhc3M9ImZhcmdlIiBkPSJNOTcuODQsMjEyLjIyYzQuMDUsMy45NywxMS4yNiwxNS4zOCwxNC40MywyMC4xN2M5LjMyLDE0LjM5LDkuNSwxNS41NSwxOS4xOCwyOS42CgkJYzAuNTMsMC44MywzLjM0LDQuOTYsMy4zNCw2LjQ1YzAsMC45OS03LjkyLDEuNDktMTQuNiwxLjQ5Yy00Ljc1LDAtOC45Ny0wLjE3LTkuNjgtMC45OWMtMi42NC0zLjE0LTIuODItMy44LTUuMjgtNy43NwoJCWMtNy45Mi0xMi43My0xMy4xOS0yMy40OC0yMy43NS0zOC43Yy0wLjE4LTAuMzMtMS43Ni0wLjMzLTIuNjQtMC42NnYwLjY2YzAsMS45OCwwLDQuMTQsMCw2LjEyYzAsNC4xMywwLDguMSwwLDEyLjI0djcuNzcKCQljMCw0LjYzLDAuMzUsMTAuNzUsMC4zNSwxNS4wNWMwLDMuMzEtMC4xOCw1LjYyLTAuODgsNS42MmgtMi4yOWgtNy4wNGMtNS45OCwwLTEyLjE0LTAuMTYtMTMuMDItMC45OQoJCWMwLTMyLjQxLDEuMDYtNzIuNDMsMi40Ni0xMDEuNTRsMC4xOC0yLjMxYzAuMTgtMy4xNCwwLjE4LTkuMSwwLjE4LTkuMWg2LjUxYzYuNjksMCwxNC4yNSwwLjE3LDE0LjI1LDIuMzJ2Ni42MXY0LjEzdjIyLjMyCgkJYzAsNC4zLDAsOC4xLTAuMTgsMTEuOTFjMTEuNjEtMTMuNTYsMTguMy0yOC42MSwzMC4yNi00My42NmMwLjUzLTAuMzMsMS43Ni0wLjMzLDMuMzQtMC4zM2M2Ljg2LDAsMjEuMjksMi4xNSwyMi4xNywzLjMxCgkJQzExNi4xNCwxODkuNCwxMDkuMSwxOTguOTksOTcuODQsMjEyLjIyeiBNNjguNjQsMjQ2LjI5Yy0wLjctMC44My0xLjU4LTEuMTYtMi42NC0xLjE2Yy0xLjIzLDAtMi4xMSwwLjMzLTIuODEsMS4xNgoJCWMtMC43LDAuNjYtMS4wNiwxLjQ5LTEuMDYsMi40OGMwLDAuOTksMC4zNSwxLjk4LDEuMDYsMi42NWMwLjcsMC42NiwxLjU4LDAuOTksMi44MSwwLjk5YzEuMDYsMCwxLjkzLTAuMzMsMi42NC0wLjk5CgkJczEuMDYtMS42NSwxLjA2LTIuNjVDNjkuNjksMjQ3Ljc4LDY5LjM0LDI0Ni45NSw2OC42NCwyNDYuMjl6IE02OC44MSwyMjIuMTRjLTAuNy0wLjgzLTEuNTgtMS4xNi0yLjY0LTEuMTYKCQljLTEuMjMsMC0yLjExLDAuMzMtMi44MiwxLjE2Yy0wLjcsMC42Ni0xLjA2LDEuNDktMS4wNiwyLjQ4YzAsMC45OSwwLjM1LDEuOTgsMS4wNiwyLjY1YzAuNywwLjY2LDEuNTgsMC45OSwyLjgyLDAuOTkKCQljMS4wNSwwLDEuOTMtMC4zMywyLjY0LTAuOTljMC43LTAuNjYsMS4wNS0xLjY1LDEuMDUtMi42NUM2OS44NywyMjMuNjMsNjkuNTIsMjIyLjgsNjguODEsMjIyLjE0eiBNNjguOTksMjM0LjIyCgkJYy0wLjg4LTAuNjYtMS43Ni0wLjk5LTIuODEtMC45OWMtMS4wNiwwLTEuOTMsMC4zMy0yLjgyLDAuOTljLTAuNywwLjgzLTEuMDYsMS42NS0xLjA2LDIuNjRjMCwwLjk5LDAuMzUsMS44MiwxLjA2LDIuNjUKCQljMC44OCwwLjY2LDEuNzYsMC45OSwyLjgyLDAuOTljMS4wNSwwLDEuOTMtMC4zMywyLjgxLTAuOTljMC43LTAuODMsMS4wNi0xLjY1LDEuMDYtMi42NUM3MC4wNCwyMzUuODcsNjkuNjksMjM1LjA0LDY4Ljk5LDIzNC4yMnoKCQkgTTY4Ljk5LDIwOS4wOGMtMC43LTAuNjYtMS41OC0wLjk5LTIuNjQtMC45OWMtMS4wNiwwLTIuMTEsMC4zMy0yLjgxLDAuOTljLTAuNywwLjY2LTEuMDYsMS40OS0xLjA2LDIuNjUKCQljMCwwLjk5LDAuMzUsMS44MiwxLjA2LDIuNDhjMC43LDAuNjYsMS43NiwxLjE2LDIuODEsMS4xNmMxLjA2LDAsMS45My0wLjUsMi42NC0xLjE2YzAuODgtMC42NiwxLjIzLTEuNDksMS4yMy0yLjQ4CgkJQzcwLjIyLDIxMC41Nyw2OS44NywyMDkuNzQsNjguOTksMjA5LjA4eiBNNjkuMTcsMjU3LjM3Yy0wLjg4LTAuNjYtMS43Ni0wLjk5LTIuODItMC45OWMtMS4wNiwwLTEuOTMsMC4zMy0yLjgxLDAuOTkKCQljLTAuNywwLjY2LTEuMDYsMS42NS0xLjA2LDIuNjVjMCwwLjk5LDAuMzUsMS44MiwxLjA2LDIuNDhjMC44OCwwLjgzLDEuNzYsMS4xNiwyLjgxLDEuMTZjMS4wNiwwLDEuOTMtMC4zMywyLjgyLTEuMTYKCQljMC43LTAuNjYsMS4wNS0xLjQ5LDEuMDUtMi40OEM3MC4yMiwyNTkuMDIsNjkuODcsMjU4LjAzLDY5LjE3LDI1Ny4zN3ogTTY5LjUyLDE4My4xMmMtMC43LTAuNjYtMS41OC0wLjk5LTIuODItMC45OQoJCWMtMS4wNiwwLTEuOTMsMC4zMy0yLjY0LDAuOTljLTAuNywwLjY2LTEuMjMsMS40OS0xLjIzLDIuNjVjMCwwLjk5LDAuNTMsMS44MiwxLjIzLDIuNDhjMC43LDAuNjYsMS41OCwwLjk5LDIuNjQsMC45OQoJCWMxLjIzLDAsMi4xMS0wLjMzLDIuODItMC45OWMwLjctMC42NiwxLjA1LTEuNDksMS4wNS0yLjQ4QzcwLjU3LDE4NC42LDcwLjIyLDE4My43OCw2OS41MiwxODMuMTJ6IE02OS42OSwxNzEuMjEKCQljLTAuODgtMC44My0xLjc2LTEuMTYtMi44MS0xLjE2Yy0xLjA2LDAtMS45NCwwLjMzLTIuODIsMS4xNmMtMC43LDAuNjYtMS4wNiwxLjQ5LTEuMDYsMi40OGMwLDAuOTksMC4zNSwxLjk4LDEuMDYsMi42NQoJCWMwLjg4LDAuNjYsMS43NiwwLjk5LDIuODIsMC45OWMxLjA1LDAsMS45My0wLjMzLDIuODEtMC45OWMwLjctMC42NiwxLjA2LTEuNjUsMS4wNi0yLjY1QzcwLjc1LDE3Mi43LDcwLjQsMTcxLjg3LDY5LjY5LDE3MS4yMXoKCQkgTTY5Ljg3LDE5NS42OGMtMC43LTAuNjYtMS41OC0wLjk5LTIuNjQtMC45OWMtMS4wNSwwLTIuMTEsMC4zMy0yLjgxLDAuOTljLTAuNywwLjY2LTEuMDYsMS42NS0xLjA2LDIuNjVzMC4zNSwxLjgyLDEuMDYsMi40OAoJCWMwLjcsMC44MywxLjc2LDEuMTYsMi44MSwxLjE2YzEuMDYsMCwxLjk0LTAuMzMsMi42NC0xLjE2YzAuODgtMC42NiwxLjIzLTEuNDksMS4yMy0yLjQ4UzcwLjc1LDE5Ni4zNCw2OS44NywxOTUuNjh6IE03MC41NywxNTkuMwoJCWMtMC43LTAuODMtMS43Ni0xLjE2LTIuODEtMS4xNnMtMS45MywwLjMzLTIuNjQsMS4xNmMtMC44OCwwLjY2LTEuMjMsMS40OS0xLjIzLDIuNDhjMCwwLjk5LDAuMzUsMS45OCwxLjIzLDIuNjUKCQljMC43LDAuNjYsMS41OCwwLjk5LDIuNjQsMC45OXMyLjExLTAuMzMsMi44MS0wLjk5YzAuNy0wLjY2LDEuMDYtMS42NSwxLjA2LTIuNjVDNzEuNjMsMTYwLjc5LDcxLjI4LDE1OS45Niw3MC41NywxNTkuM3oKCQkgTTgwLjQyLDIwOC43NWMtMC44OC0wLjY2LTEuNzYtMC45OS0yLjgyLTAuOTljLTEuMDYsMC0xLjkzLDAuMzMtMi44MSwwLjk5Yy0wLjcsMC44My0xLjA2LDEuNjUtMS4wNiwyLjY1CgkJYzAsMC45OSwwLjM1LDEuODIsMS4wNiwyLjY1YzAuODgsMC42NiwxLjc2LDAuOTksMi44MSwwLjk5YzEuMDYsMCwxLjkzLTAuMzMsMi44Mi0wLjk5YzAuNy0wLjgzLDEuMDUtMS42NSwxLjA1LTIuNjUKCQlDODEuNDgsMjEwLjQsODEuMTMsMjA5LjU3LDgwLjQyLDIwOC43NXogTTkxLjY4LDIwOC4wOWMtMC44OC0wLjY2LTEuNzYtMC45OS0yLjgyLTAuOTljLTEuMDYsMC0xLjkzLDAuMzMtMi44MSwwLjk5CgkJYy0wLjcsMC44My0xLjA2LDEuNjUtMS4wNiwyLjY0YzAsMC45OSwwLjM1LDEuODIsMS4wNiwyLjY1YzAuODgsMC42NiwxLjc2LDAuOTksMi44MSwwLjk5YzEuMDYsMCwxLjkzLTAuMzMsMi44Mi0wLjk5CgkJYzAuNy0wLjgzLDEuMDUtMS42NSwxLjA1LTIuNjVDOTIuNzQsMjA5Ljc0LDkyLjM5LDIwOC45MSw5MS42OCwyMDguMDl6IE05Ni4yNiwyMTguNTFjLTAuNy0wLjY2LTEuNTgtMS4xNi0yLjgyLTEuMTYKCQljLTEuMDYsMC0xLjkzLDAuNS0yLjY0LDEuMTZjLTAuNywwLjY2LTEuMjMsMS40OS0xLjIzLDIuNDhjMCwxLjE2LDAuNTMsMS45OCwxLjIzLDIuNjVjMC43LDAuNjYsMS41OCwwLjk5LDIuNjQsMC45OQoJCWMxLjIzLDAsMi4xMS0wLjMzLDIuODItMC45OWMwLjctMC42NiwxLjA2LTEuNDksMS4wNi0yLjY1Qzk3LjMxLDIxOS45OSw5Ni45NiwyMTkuMTcsOTYuMjYsMjE4LjUxeiBNOTcuMTQsMTk3LjAxCgkJYy0wLjctMC42Ni0xLjU4LTAuOTktMi44MS0wLjk5Yy0xLjA2LDAtMS45NCwwLjMzLTIuNjQsMC45OWMtMC43LDAuNjYtMS4wNiwxLjY1LTEuMDYsMi42NWMwLDAuOTksMC4zNSwxLjgyLDEuMDYsMi40OAoJCWMwLjcsMC44MywxLjU4LDEuMTYsMi42NCwxLjE2YzEuMjMsMCwyLjExLTAuMzMsMi44MS0xLjE2YzAuNy0wLjY2LDEuMDYtMS40OSwxLjA2LTIuNDhDOTguMTksMTk4LjY2LDk3Ljg0LDE5Ny42Nyw5Ny4xNCwxOTcuMDF6CgkJIE0xMDIuMjQsMjI3LjZjLTAuNy0wLjY2LTEuNTgtMS4xNi0yLjgyLTEuMTZjLTEuMDYsMC0xLjkzLDAuNS0yLjY0LDEuMTZzLTEuMjMsMS40OS0xLjIzLDIuNDhjMCwxLjE2LDAuNTMsMS45OCwxLjIzLDIuNjUKCQlzMS41OCwwLjk5LDIuNjQsMC45OWMxLjIzLDAsMi4xMS0wLjMzLDIuODItMC45OXMxLjA2LTEuNDksMS4wNi0yLjY1QzEwMy4yOSwyMjkuMDksMTAyLjk0LDIyOC4yNiwxMDIuMjQsMjI3LjZ6IE0xMDUuMDUsMTg2Ljc1CgkJYy0wLjctMC42Ni0xLjU4LTAuOTktMi42NC0wLjk5Yy0xLjIzLDAtMi4xMSwwLjMzLTIuODEsMC45OWMtMC43LDAuNjYtMS4wNiwxLjQ5LTEuMDYsMi40OGMwLDEuMTYsMC4zNSwxLjk4LDEuMDYsMi42NQoJCWMwLjcsMC42NiwxLjU4LDAuOTksMi44MSwwLjk5YzEuMDYsMCwxLjkzLTAuMzMsMi42NC0wLjk5YzAuNy0wLjY2LDEuMDYtMS40OSwxLjA2LTIuNjUKCQlDMTA2LjExLDE4OC4yNCwxMDUuNzYsMTg3LjQxLDEwNS4wNSwxODYuNzV6IE0xMDguMDQsMjM3LjAzYy0wLjctMC44My0xLjU4LTEuMTYtMi42NC0xLjE2Yy0xLjA2LDAtMi4xMSwwLjMzLTIuODEsMS4xNgoJCWMtMC43LDAuNjYtMS4wNiwxLjQ5LTEuMDYsMi40OHMwLjM1LDEuOTgsMS4wNiwyLjY1czEuNzYsMC45OSwyLjgxLDAuOTljMS4wNiwwLDEuOTMtMC4zMywyLjY0LTAuOTkKCQljMC44OC0wLjY2LDEuMjMtMS42NSwxLjIzLTIuNjVTMTA4LjkyLDIzNy42OSwxMDguMDQsMjM3LjAzeiBNMTExLjkxLDE3NC42OGMtMC43LTAuNjYtMS41OC0wLjk5LTIuNjQtMC45OQoJCWMtMS4wNiwwLTIuMTEsMC4zMy0yLjgyLDAuOTlzLTEuMDYsMS42NS0xLjA2LDIuNjVjMCwwLjk5LDAuMzUsMS44MiwxLjA2LDIuNDhjMC43LDAuODMsMS43NiwxLjE2LDIuODIsMS4xNgoJCWMxLjA1LDAsMS45My0wLjMzLDIuNjQtMS4xNmMwLjg4LTAuNjYsMS4yMy0xLjQ5LDEuMjMtMi40OEMxMTMuMTQsMTc2LjM0LDExMi43OSwxNzUuMzQsMTExLjkxLDE3NC42OHogTTExNS4yNiwyNDcuNjEKCQljLTAuNy0wLjY2LTEuNTgtMS4xNi0yLjgxLTEuMTZjLTEuMDYsMC0xLjkzLDAuNS0yLjY0LDEuMTZzLTEuMjMsMS40OS0xLjIzLDIuNDhjMCwxLjE2LDAuNTMsMS45OCwxLjIzLDIuNjVzMS41OCwwLjk5LDIuNjQsMC45OQoJCWMxLjIzLDAsMi4xMS0wLjMzLDIuODEtMC45OWMwLjctMC42NiwxLjA2LTEuNDksMS4wNi0yLjY1QzExNi4zMSwyNDkuMSwxMTUuOTYsMjQ4LjI3LDExNS4yNiwyNDcuNjF6IE0xMTkuNDgsMTYzLjc3CgkJYy0wLjctMC42Ni0xLjU4LTAuOTktMi42NC0wLjk5Yy0xLjIzLDAtMi4xMSwwLjMzLTIuODIsMC45OWMtMC43LDAuODMtMS4wNiwxLjY1LTEuMDYsMi42NWMwLDAuOTksMC4zNSwxLjgyLDEuMDYsMi40OAoJCWMwLjcsMC44MywxLjU4LDEuMTYsMi44MiwxLjE2YzEuMDUsMCwxLjkzLTAuMzMsMi42NC0xLjE2YzAuNy0wLjY2LDEuMDYtMS40OSwxLjA2LTIuNDgKCQlDMTIwLjUzLDE2NS40MiwxMjAuMTgsMTY0LjU5LDExOS40OCwxNjMuNzd6IE0xMjIuMTIsMjU4LjAzYy0wLjctMC42Ni0xLjU4LTEuMTYtMi44Mi0xLjE2Yy0xLjA2LDAtMS45MywwLjUtMi42NCwxLjE2CgkJYy0wLjcsMC42Ni0xLjIzLDEuNDktMS4yMywyLjQ4YzAsMS4xNiwwLjUzLDEuOTgsMS4yMywyLjY1YzAuNywwLjY2LDEuNTgsMC45OSwyLjY0LDAuOTljMS4yMywwLDIuMTEtMC4zMywyLjgyLTAuOTkKCQljMC43LTAuNjYsMS4wNi0xLjQ5LDEuMDYtMi42NUMxMjMuMTcsMjU5LjUyLDEyMi44MiwyNTguNjksMTIyLjEyLDI1OC4wM3oiLz4KCTxwYXRoIGNsYXNzPSJmYXJnZSIgZD0iTTE5MC4yLDE4Mi4xMmM3LjIxLDQuOTYsMTIuMTQsMTEuNTgsMTUuNjYsMTcuODZjMy4xNyw1Ljk1LDUuMSwxMi43Myw1LjEsMTkuODVjMCw2LjEyLTEuNDEsMTIuMjQtNC43NSwxOC4xOQoJCWMtOC4wOSwxNC4zOS0xOS44OCwyMi40OS0zMy43OCwyMi40OWMtNy43NCwwLTE2LjAxLTIuNDgtMjQuOC03LjYxYy0xMS43OS03LjExLTE5LTE4Ljg1LTIxLjExLTMyLjc0CgkJYy0wLjE4LTEuNjUtMC41My0zLjQ3LTAuNTMtNS4yOWMwLTYuNDUsMS41OC0xMy40LDUuNDUtMjAuODRjMi45OS01LjQ2LDUuOTgtOS4xLDkuNS0xMi4wN2M3LjIxLTYuMTIsMTUuNDgtOS4yNiwyNC44LTkuMjYKCQljMS40MSwwLDIuOTksMCw0LjU3LDAuMTdDMTc2LjY1LDE3My4zNiwxODQuNTcsMTc3LjQ5LDE5MC4yLDE4Mi4xMnogTTEzOSwyMDUuNDRjLTAuNy0wLjgzLTEuNTgtMS4xNi0yLjY0LTEuMTYKCQljLTEuMDYsMC0yLjExLDAuMzMtMi44MSwxLjE2Yy0wLjcsMC42Ni0xLjA2LDEuNDktMS4wNiwyLjQ4YzAsMC45OSwwLjM1LDEuODIsMS4wNiwyLjY1YzAuNywwLjY2LDEuNzYsMC45OSwyLjgxLDAuOTkKCQljMS4wNiwwLDEuOTMtMC4zMywyLjY0LTAuOTljMC44OC0wLjgzLDEuMjMtMS42NSwxLjIzLTIuNjVDMTQwLjI0LDIwNi45MywxMzkuODgsMjA2LjEsMTM5LDIwNS40NHogTTE0MS4xMiwyMTcuODQKCQljLTAuNy0wLjgzLTEuNTgtMS4xNi0yLjgyLTEuMTZjLTEuMDYsMC0xLjkzLDAuMzMtMi42NCwxLjE2Yy0wLjcsMC42Ni0xLjIzLDEuNDktMS4yMywyLjQ4YzAsMC45OSwwLjUzLDEuOTgsMS4yMywyLjY1CgkJYzAuNywwLjY2LDEuNTgsMC45OSwyLjY0LDAuOTljMS4yMywwLDIuMTEtMC4zMywyLjgyLTAuOTljMC43LTAuNjYsMS4wNi0xLjY1LDEuMDYtMi42NQoJCUMxNDIuMTcsMjE5LjMzLDE0MS44MiwyMTguNTEsMTQxLjEyLDIxNy44NHogTTE0Mi44NywxOTQuNjljLTAuODgtMC42Ni0xLjc2LTAuOTktMi44MS0wLjk5Yy0xLjA2LDAtMS45MywwLjMzLTIuODIsMC45OQoJCWMtMC43LDAuODMtMS4wNSwxLjY1LTEuMDUsMi42NWMwLDAuOTksMC4zNSwxLjgyLDEuMDUsMi42NWMwLjg4LDAuNjYsMS43NiwwLjk5LDIuODIsMC45OWMxLjA2LDAsMS45My0wLjMzLDIuODEtMC45OQoJCWMwLjctMC44MywxLjA2LTEuNjUsMS4wNi0yLjY1QzE0My45MywxOTYuMzQsMTQzLjU4LDE5NS41MiwxNDIuODcsMTk0LjY5eiBNMTQ1LjM0LDIzMC4yNWMtMC44OC0wLjY2LTEuNzYtMC45OS0yLjgyLTAuOTkKCQljLTEuMDUsMC0xLjkzLDAuMzMtMi44MSwwLjk5Yy0wLjcsMC44My0xLjA2LDEuNjUtMS4wNiwyLjY1YzAsMC45OSwwLjM1LDEuODIsMS4wNiwyLjY0YzAuODgsMC42NiwxLjc2LDAuOTksMi44MSwwLjk5CgkJYzEuMDYsMCwxLjk0LTAuMzMsMi44Mi0wLjk5YzAuNy0wLjgzLDEuMDYtMS42NSwxLjA2LTIuNjRDMTQ2LjM5LDIzMS45LDE0Ni4wNCwyMzEuMDcsMTQ1LjM0LDIzMC4yNXogTTE1MS4zMiwxODQuNgoJCWMtMC44OC0wLjY2LTEuNzYtMC45OS0yLjgyLTAuOTljLTEuMDUsMC0xLjkzLDAuMzMtMi44MSwwLjk5Yy0wLjcsMC44My0xLjA2LDEuNjUtMS4wNiwyLjY1YzAsMC45OSwwLjM1LDEuODIsMS4wNiwyLjY1CgkJYzAuODgsMC42NiwxLjc2LDAuOTksMi44MSwwLjk5YzEuMDYsMCwxLjk0LTAuMzMsMi44Mi0wLjk5YzAuNy0wLjgzLDEuMDYtMS42NSwxLjA2LTIuNjUKCQlDMTUyLjM3LDE4Ni4yNiwxNTIuMDIsMTg1LjQzLDE1MS4zMiwxODQuNnogTTE3NS43NywxOTYuNjhjLTQuMjItMi44MS04LjYyLTQuMy0xMi44NC00LjNjLTguOTcsMC0xNi41NCw2LjQ1LTE2LjU0LDE3LjY5CgkJYzAsMS4zMiwwLjE4LDIuODEsMC4zNSw0LjNjMS4yMyw1Ljc5LDIuNDYsMTEuNDEsNi4xNiwxNi4zN2M0Ljc1LDYuNjIsMTAuNzMsMTAuNTgsMTYuMzYsMTAuNThjNS4xLDAsMTAuMi0zLjMxLDE0LjYtMTAuNTgKCQljMC43LTEuMzIsMS41OC0yLjY1LDIuMjktMy45N2MwLjg4LTIuODEsMS40MS01LjYyLDEuNDEtOC4yN2MwLTAuOTktMC4xOC0yLjE1LTAuMzUtMy4xNAoJCUMxODYuMTUsMjA4Ljc1LDE4Mi44MSwyMDEuNDcsMTc1Ljc3LDE5Ni42OHogTTE1NC44NCwyNDEuMzNjLTAuNy0wLjY2LTEuNTgtMC45OS0yLjgxLTAuOTljLTEuMDYsMC0xLjkzLDAuMzMtMi42NCwwLjk5CgkJYy0wLjcsMC42Ni0xLjIzLDEuNDktMS4yMywyLjY1YzAsMC45OSwwLjUzLDEuODIsMS4yMywyLjQ4YzAuNywwLjY2LDEuNTgsMS4xNiwyLjY0LDEuMTZjMS4yMywwLDIuMTEtMC41LDIuODEtMS4xNgoJCWMwLjctMC42NiwxLjA2LTEuNDksMS4wNi0yLjQ4QzE1NS44OSwyNDIuODEsMTU1LjU0LDI0MS45OSwxNTQuODQsMjQxLjMzeiBNMTYyLjU4LDE3OS4xNWMtMC43LTAuNjYtMS41OC0wLjk5LTIuNjQtMC45OQoJCWMtMS4wNiwwLTIuMTEsMC4zMy0yLjgyLDAuOTljLTAuNywwLjgzLTEuMDUsMS42NS0xLjA1LDIuNjVjMCwwLjk5LDAuMzUsMS44MiwxLjA1LDIuNDhjMC43LDAuODMsMS43NiwxLjE2LDIuODIsMS4xNgoJCWMxLjA2LDAsMS45My0wLjMzLDIuNjQtMS4xNmMwLjg4LTAuNjYsMS4yMy0xLjQ5LDEuMjMtMi40OEMxNjMuODEsMTgwLjgsMTYzLjQ2LDE3OS45NywxNjIuNTgsMTc5LjE1eiBNMTY5LjQ0LDI0Ni4xMgoJCWMtMC44OC0wLjgzLTEuNzYtMS4xNi0yLjgxLTEuMTZjLTEuMDYsMC0xLjkzLDAuMzMtMi44MiwxLjE2Yy0wLjcsMC42Ni0xLjA2LDEuNDktMS4wNiwyLjQ4YzAsMC45OSwwLjM1LDEuODIsMS4wNiwyLjY1CgkJYzAuODgsMC42NiwxLjc2LDAuOTksMi44MiwwLjk5YzEuMDYsMCwxLjkzLTAuMzMsMi44MS0wLjk5YzAuNy0wLjgzLDEuMDYtMS42NSwxLjA2LTIuNjUKCQlDMTcwLjQ5LDI0Ny42MSwxNzAuMTQsMjQ2Ljc4LDE2OS40NCwyNDYuMTJ6IE0xNzQuODksMTgwLjk3Yy0wLjctMC42Ni0xLjU4LTAuOTktMi44Mi0wLjk5Yy0xLjA2LDAtMS45MywwLjMzLTIuNjQsMC45OQoJCWMtMC43LDAuNjYtMS4yMywxLjQ5LTEuMjMsMi42NWMwLDAuOTksMC41MywxLjgyLDEuMjMsMi40OGMwLjcsMC42NiwxLjU4LDEuMTYsMi42NCwxLjE2YzEuMjMsMCwyLjExLTAuNSwyLjgyLTEuMTYKCQljMC43LTAuNjYsMS4wNi0xLjQ5LDEuMDYtMi40OEMxNzUuOTUsMTgyLjQ1LDE3NS42LDE4MS42MywxNzQuODksMTgwLjk3eiBNMTgzLjE2LDI0NC40N2MtMC43LTAuNjYtMS41OC0wLjk5LTIuODItMC45OQoJCWMtMS4wNiwwLTEuOTMsMC4zMy0yLjY0LDAuOTljLTAuNywwLjgzLTEuMDYsMS42NS0xLjA2LDIuNjVjMCwwLjk5LDAuMzUsMS44MiwxLjA2LDIuNDhjMC43LDAuODMsMS41OCwxLjE2LDIuNjQsMS4xNgoJCWMxLjIzLDAsMi4xMS0wLjMzLDIuODItMS4xNmMwLjctMC42NiwxLjA2LTEuNDksMS4wNi0yLjQ4QzE4NC4yMiwyNDYuMTIsMTgzLjg2LDI0NS4zLDE4My4xNiwyNDQuNDd6IE0xODUuOCwxODcuNDEKCQljLTAuNy0wLjY2LTEuNTgtMC45OS0yLjgyLTAuOTljLTEuMDUsMC0xLjkzLDAuMzMtMi42NCwwLjk5cy0xLjA2LDEuNDktMS4wNiwyLjY1YzAsMC45OSwwLjM1LDEuODIsMS4wNiwyLjQ4CgkJYzAuNywwLjY2LDEuNTgsMC45OSwyLjY0LDAuOTljMS4yMywwLDIuMTEtMC4zMywyLjgyLTAuOTljMC43LTAuNjYsMS4wNi0xLjQ5LDEuMDYtMi40OEMxODYuODUsMTg4LjksMTg2LjUsMTg4LjA4LDE4NS44LDE4Ny40MXoKCQkgTTE5Mi42NiwyMzUuMDRjLTAuNy0wLjgzLTEuNTgtMS4xNi0yLjY0LTEuMTZjLTEuMDYsMC0yLjExLDAuMzMtMi44MSwxLjE2Yy0wLjcsMC42Ni0xLjA2LDEuNDktMS4wNiwyLjQ4CgkJYzAsMC45OSwwLjM1LDEuODIsMS4wNiwyLjY1YzAuNywwLjY2LDEuNzYsMC45OSwyLjgxLDAuOTljMS4wNiwwLDEuOTMtMC4zMywyLjY0LTAuOTljMC44OC0wLjgzLDEuMjMtMS42NSwxLjIzLTIuNjUKCQlDMTkzLjg5LDIzNi41MywxOTMuNTQsMjM1LjcsMTkyLjY2LDIzNS4wNHogTTE5NC41OSwxOTcuMDFjLTAuNy0wLjgzLTEuNzYtMS4xNi0yLjgxLTEuMTZjLTEuMDYsMC0xLjkzLDAuMzMtMi42NCwxLjE2CgkJYy0wLjg4LDAuNjYtMS4yMywxLjQ5LTEuMjMsMi40OGMwLDAuOTksMC4zNSwxLjk4LDEuMjMsMi42NWMwLjcsMC42NiwxLjU4LDAuOTksMi42NCwwLjk5YzEuMDYsMCwyLjExLTAuMzMsMi44MS0wLjk5CgkJczEuMDYtMS42NSwxLjA2LTIuNjVDMTk1LjY1LDE5OC40OSwxOTUuMywxOTcuNjcsMTk0LjU5LDE5Ny4wMXogTTE5OS44NywyMTAuMDdjLTAuNy0wLjY2LTEuNzYtMC45OS0yLjgxLTAuOTkKCQljLTEuMDYsMC0xLjkzLDAuMzMtMi42NCwwLjk5Yy0wLjg4LDAuODMtMS4yMywxLjY1LTEuMjMsMi42NXMwLjM1LDEuODIsMS4yMywyLjY1YzAuNywwLjY2LDEuNTgsMC45OSwyLjY0LDAuOTkKCQljMS4wNiwwLDIuMTEtMC4zMywyLjgxLTAuOTljMC43LTAuODMsMS4wNi0xLjY1LDEuMDYtMi42NVMyMDAuNTgsMjEwLjksMTk5Ljg3LDIxMC4wN3ogTTE5OS44NywyMjMuOTYKCQljLTAuNy0wLjgzLTEuNzYtMS4xNi0yLjgxLTEuMTZjLTEuMDYsMC0xLjkzLDAuMzMtMi42NCwxLjE2Yy0wLjg4LDAuNjYtMS4yMywxLjQ5LTEuMjMsMi40OGMwLDAuOTksMC4zNSwxLjk4LDEuMjMsMi42NQoJCWMwLjcsMC42NiwxLjU4LDAuOTksMi42NCwwLjk5YzEuMDYsMCwyLjExLTAuMzMsMi44MS0wLjk5YzAuNy0wLjY2LDEuMDYtMS42NSwxLjA2LTIuNjUKCQlDMjAwLjkzLDIyNS40NSwyMDAuNTgsMjI0LjYyLDE5OS44NywyMjMuOTZ6Ii8+Cgk8cGF0aCBjbGFzcz0iZmFyZ2UiIGQ9Ik0yMjAuOTgsMjYxLjM0di03Ljk0di0zLjhjMC0yLjQ4LTAuMTctNC45Ni0wLjE3LTcuNDRjLTAuMzUtMTUuNzEtMS4yMy0zNi44OC0xLjIzLTUzLjc1di0xMS4yNQoJCWMwLTUuNjIsMC4xOC0xMS4yNSwwLjM1LTE2LjA0YzAtMC42NiwyLjY0LTAuODMsNS45OC0wLjgzYzUuNjMsMCwxMy41NCwwLjUsMTUuMzEsMC44M2M5LjUsMS4zMiwxOS43LDUuNDYsMjcuMDksMTAuMjUKCQljNy45Miw1LjEzLDE1LjgzLDE2Ljg3LDE2LjE4LDI4LjYxYzAsMy40NywwLDE1Ljg4LTAuNTMsMTkuMDJjLTIuOTksMTkuNjgtMTMuMDIsMzIuMDgtMjcuNDQsNDEuMTgKCQljLTUuMjgsMy4zMS0xMS4yNiw1LjEzLTE4LjEyLDYuNzhjLTIuMTEsMC41LTQuMjIsMC45OS03LjA0LDAuOTljLTIuNDYsMC00LjkzLDAuMzMtNi44NiwwLjMzYy0yLjExLDAtMy41Mi0wLjMzLTMuNTItMS42NVYyNjEuMzQKCQl6IE0yMzAuMzEsMTY3LjA3Yy0wLjg4LTAuNjYtMS43Ni0wLjk5LTIuODEtMC45OWMtMS4wNiwwLTEuOTQsMC4zMy0yLjgyLDAuOTljLTAuNywwLjY2LTEuMDYsMS42NS0xLjA2LDIuNjUKCQljMCwwLjk5LDAuMzUsMS44MiwxLjA2LDIuNDhjMC44OCwwLjY2LDEuNzYsMS4xNiwyLjgyLDEuMTZjMS4wNSwwLDEuOTMtMC41LDIuODEtMS4xNmMwLjctMC42NiwxLjA2LTEuNDksMS4wNi0yLjQ4CgkJQzIzMS4zNiwxNjguNzMsMjMxLjAxLDE2Ny43NCwyMzAuMzEsMTY3LjA3eiBNMjMwLjgzLDE3OS40OGMtMC43LTAuODMtMS43Ni0xLjE2LTIuODEtMS4xNmMtMS4wNiwwLTEuOTMsMC4zMy0yLjY0LDEuMTYKCQljLTAuODgsMC42Ni0xLjIzLDEuNDktMS4yMywyLjQ4YzAsMC45OSwwLjM1LDEuOTgsMS4yMywyLjY1YzAuNywwLjY2LDEuNTgsMC45OSwyLjY0LDAuOTljMS4wNSwwLDIuMTEtMC4zMywyLjgxLTAuOTkKCQljMC43LTAuNjYsMS4wNi0xLjY1LDEuMDYtMi42NUMyMzEuODksMTgwLjk3LDIzMS41NCwxODAuMTQsMjMwLjgzLDE3OS40OHogTTIzMS4xOSwyMDMuOTVjLTAuNy0wLjY2LTEuNTgtMS4xNi0yLjgyLTEuMTYKCQljLTEuMDUsMC0xLjkzLDAuNS0yLjY0LDEuMTZjLTAuNywwLjY2LTEuMjMsMS40OS0xLjIzLDIuNDhjMCwxLjE2LDAuNTMsMS45OCwxLjIzLDIuNjVjMC43LDAuNjYsMS41OCwwLjk5LDIuNjQsMC45OQoJCWMxLjIzLDAsMi4xMS0wLjMzLDIuODItMC45OWMwLjctMC42NiwxLjA1LTEuNDksMS4wNS0yLjY1QzIzMi4yNCwyMDUuNDQsMjMxLjg5LDIwNC42MSwyMzEuMTksMjAzLjk1eiBNMjMxLjM2LDIxNy4zNQoJCWMtMC43LTAuNjYtMS43Ni0xLjE2LTIuODItMS4xNnMtMS45MywwLjUtMi42NCwxLjE2Yy0wLjg4LDAuNjYtMS4yMywxLjQ5LTEuMjMsMi40OGMwLDEuMTYsMC4zNSwxLjk4LDEuMjMsMi42NAoJCWMwLjcsMC42NiwxLjU4LDAuOTksMi42NCwwLjk5czIuMTEtMC4zMywyLjgyLTAuOTljMC43LTAuNjYsMS4wNS0xLjQ5LDEuMDUtMi42NEMyMzIuNDIsMjE4Ljg0LDIzMi4wNywyMTguMDEsMjMxLjM2LDIxNy4zNXoKCQkgTTIzMS41NCwxOTEuMjJjLTAuNy0wLjgzLTEuNzYtMS4xNi0yLjgxLTEuMTZjLTEuMDYsMC0xLjkzLDAuMzMtMi42NCwxLjE2Yy0wLjg4LDAuNjYtMS4yMywxLjQ5LTEuMjMsMi40OAoJCWMwLDAuOTksMC4zNSwxLjk4LDEuMjMsMi42NWMwLjcsMC42NiwxLjU4LDAuOTksMi42NCwwLjk5YzEuMDUsMCwyLjExLTAuMzMsMi44MS0wLjk5YzAuNy0wLjY2LDEuMDYtMS42NSwxLjA2LTIuNjUKCQlDMjMyLjU5LDE5Mi43MSwyMzIuMjQsMTkxLjg4LDIzMS41NCwxOTEuMjJ6IE0yMzIuMDcsMjI5LjU4Yy0wLjg4LTAuNjYtMS43Ni0xLjE2LTIuODItMS4xNmMtMS4wNiwwLTEuOTMsMC41LTIuODIsMS4xNgoJCWMtMC43LDAuNjYtMS4wNSwxLjQ5LTEuMDUsMi40OGMwLDEuMTYsMC4zNSwxLjk4LDEuMDUsMi42NWMwLjg4LDAuNjYsMS43NiwwLjk5LDIuODIsMC45OWMxLjA1LDAsMS45My0wLjMzLDIuODItMC45OQoJCWMwLjctMC42NiwxLjA1LTEuNDksMS4wNS0yLjY1QzIzMy4xMiwyMzEuMDcsMjMyLjc3LDIzMC4yNSwyMzIuMDcsMjI5LjU4eiBNMjMyLjI0LDI0MS45OWMtMC43LTAuNjYtMS43Ni0xLjE2LTIuODEtMS4xNgoJCWMtMS4wNiwwLTEuOTMsMC41LTIuNjQsMS4xNmMtMC44OCwwLjY2LTEuMjMsMS40OS0xLjIzLDIuNDhjMCwxLjE2LDAuMzUsMS45OCwxLjIzLDIuNjVjMC43LDAuNjYsMS41OCwwLjk5LDIuNjQsMC45OQoJCWMxLjA1LDAsMi4xMS0wLjMzLDIuODEtMC45OWMwLjctMC42NiwxLjA2LTEuNDksMS4wNi0yLjY1QzIzMy4zLDI0My40OCwyMzIuOTUsMjQyLjY1LDIzMi4yNCwyNDEuOTl6IE0yMzIuNDIsMjU0LjIyCgkJYy0wLjctMC42Ni0xLjc2LTEuMTYtMi44Mi0xLjE2Yy0xLjA1LDAtMS45MywwLjUtMi42NCwxLjE2cy0xLjIzLDEuNDktMS4yMywyLjQ4YzAsMC45OSwwLjUzLDEuOTgsMS4yMywyLjY1CgkJYzAuNywwLjY2LDEuNTgsMC45OSwyLjY0LDAuOTljMS4wNiwwLDIuMTEtMC4zMywyLjgyLTAuOTljMC43LTAuNjYsMS4wNi0xLjY1LDEuMDYtMi42NQoJCUMyMzMuNDcsMjU1LjcxLDIzMy4xMiwyNTQuODksMjMyLjQyLDI1NC4yMnogTTI0MS45MiwxNjcuNzRjLTAuODgtMC44My0xLjc2LTEuMTYtMi44Mi0xLjE2cy0xLjkzLDAuMzMtMi44MiwxLjE2CgkJYy0wLjcsMC42Ni0xLjA1LDEuNDktMS4wNSwyLjQ4YzAsMC45OSwwLjM1LDEuODIsMS4wNSwyLjY1YzAuODgsMC42NiwxLjc2LDAuOTksMi44MiwwLjk5czEuOTMtMC4zMywyLjgyLTAuOTkKCQljMC43LTAuODMsMS4wNS0xLjY1LDEuMDUtMi42NUMyNDIuOTcsMTY5LjIyLDI0Mi42MiwxNjguNCwyNDEuOTIsMTY3Ljc0eiBNMjQzLjY4LDI1Mi40MWMtMC44OC0wLjY2LTEuNzYtMC45OS0yLjgyLTAuOTkKCQljLTEuMDUsMC0xLjkzLDAuMzMtMi44MSwwLjk5Yy0wLjcsMC44My0xLjA2LDEuNjUtMS4wNiwyLjY1czAuMzUsMS44MiwxLjA2LDIuNjVjMC44OCwwLjY2LDEuNzYsMC45OSwyLjgxLDAuOTkKCQljMS4wNiwwLDEuOTMtMC4zMywyLjgyLTAuOTljMC43LTAuODMsMS4wNi0xLjY1LDEuMDYtMi42NVMyNDQuMzgsMjUzLjIzLDI0My42OCwyNTIuNDF6IE0yMzkuNDUsMTg2LjQydjQuOTZ2MTguNTIKCQljMCw4LjQzLDAuMTgsMjEuOTksMC4xOCwzMC40M3Y2Ljc4YzIuNjQtMC42Niw0Ljc1LTEuODIsNi42OS0zLjMxYzEwLjItNy40NCwxNy4wNi0yMS45OSwxNy4wNi0zOS4xOWMwLTAuOTksMC4xOC0yLjE1LDAuMTgtMy4xNAoJCWMwLTExLjkxLTExLjA4LTE5LjAyLTIzLjkzLTE5LjAyQzIzOS40NSwxODMuNzgsMjM5LjQ1LDE4NS4xLDIzOS40NSwxODYuNDJ6IE0yNTMuODgsMTcxLjA0Yy0wLjg4LTAuNjYtMS43Ni0wLjk5LTIuODItMC45OQoJCXMtMS45MywwLjMzLTIuODIsMC45OWMtMC43LDAuODMtMS4wNSwxLjY1LTEuMDUsMi42NWMwLDAuOTksMC4zNSwxLjgyLDEuMDUsMi42NWMwLjg4LDAuNjYsMS43NiwwLjk5LDIuODIsMC45OQoJCXMxLjkzLTAuMzMsMi44Mi0wLjk5YzAuNy0wLjgzLDEuMDUtMS42NSwxLjA1LTIuNjVDMjU0Ljk0LDE3Mi43LDI1NC41OCwxNzEuODcsMjUzLjg4LDE3MS4wNHogTTI1NS4xMSwyNDYuOTUKCQljLTAuODgtMC42Ni0xLjc2LTAuOTktMi44Mi0wLjk5Yy0xLjA1LDAtMS45MywwLjMzLTIuODIsMC45OWMtMC43LDAuNjYtMS4wNSwxLjQ5LTEuMDUsMi40OGMwLDEuMTYsMC4zNSwxLjk4LDEuMDUsMi42NQoJCWMwLjg4LDAuNjYsMS43NiwwLjk5LDIuODIsMC45OWMxLjA1LDAsMS45My0wLjMzLDIuODItMC45OWMwLjctMC42NiwxLjA1LTEuNDksMS4wNS0yLjY1CgkJQzI1Ni4xNywyNDguNDQsMjU1LjgyLDI0Ny42MSwyNTUuMTEsMjQ2Ljk1eiBNMjY0LjQ0LDE3Ny4xNmMtMC44OC0wLjY2LTEuNzYtMC45OS0yLjgyLTAuOTlzLTEuOTMsMC4zMy0yLjY0LDAuOTkKCQljLTAuODgsMC44My0xLjIzLDEuNjUtMS4yMywyLjY1YzAsMC45OSwwLjM1LDEuODIsMS4yMywyLjQ4YzAuNywwLjgzLDEuNTgsMS4xNiwyLjY0LDEuMTZzMS45My0wLjMzLDIuODItMS4xNgoJCWMwLjctMC42NiwxLjA1LTEuNDksMS4wNS0yLjQ4QzI2NS40OSwxNzguODIsMjY1LjE0LDE3Ny45OSwyNjQuNDQsMTc3LjE2eiBNMjY0LjQ0LDIzOS44NGMtMC44OC0wLjY2LTEuNzYtMC45OS0yLjgyLTAuOTkKCQlzLTEuOTMsMC4zMy0yLjY0LDAuOTljLTAuODgsMC42Ni0xLjIzLDEuNjUtMS4yMywyLjY0YzAsMC45OSwwLjM1LDEuODIsMS4yMywyLjQ4YzAuNywwLjgzLDEuNTgsMS4xNiwyLjY0LDEuMTYKCQlzMS45My0wLjMzLDIuODItMS4xNmMwLjctMC42NiwxLjA1LTEuNDksMS4wNS0yLjQ4QzI2NS40OSwyNDEuNDksMjY1LjE0LDI0MC41LDI2NC40NCwyMzkuODR6IE0yNjkuNTQsMjMxLjI0CgkJYy0wLjg4LTAuNjYtMS43Ni0wLjk5LTIuODItMC45OWMtMS4wNSwwLTEuOTMsMC4zMy0yLjgxLDAuOTljLTAuNywwLjY2LTEuMDYsMS42NS0xLjA2LDIuNjVzMC4zNSwxLjgyLDEuMDYsMi40OAoJCWMwLjg4LDAuODMsMS43NiwxLjE2LDIuODEsMS4xNmMxLjA2LDAsMS45My0wLjMzLDIuODItMS4xNmMwLjctMC42NiwxLjA1LTEuNDksMS4wNS0yLjQ4UzI3MC4yNCwyMzEuOSwyNjkuNTQsMjMxLjI0egoJCSBNMjczLjU4LDE4Ni43NWMtMC44OC0wLjY2LTEuNzYtMC45OS0yLjgyLTAuOTljLTEuMDUsMC0xLjkzLDAuMzMtMi42NCwwLjk5Yy0wLjg4LDAuNjYtMS4yMywxLjQ5LTEuMjMsMi42NQoJCWMwLDAuOTksMC4zNSwxLjgyLDEuMjMsMi40OGMwLjcsMC42NiwxLjU4LDEuMTYsMi42NCwxLjE2YzEuMDYsMCwxLjkzLTAuNSwyLjgyLTEuMTZjMC43LTAuNjYsMS4wNS0xLjQ5LDEuMDUtMi40OAoJCUMyNzQuNjQsMTg4LjI0LDI3NC4yOSwxODcuNDEsMjczLjU4LDE4Ni43NXogTTI3My45MywyMTkuNWMtMC43LTAuODMtMS41OC0xLjE2LTIuODEtMS4xNmMtMS4wNiwwLTEuOTMsMC4zMy0yLjY0LDEuMTYKCQljLTAuNywwLjY2LTEuMjMsMS40OS0xLjIzLDIuNDhjMCwwLjk5LDAuNTMsMS44MiwxLjIzLDIuNjVjMC43LDAuNjYsMS41OCwwLjk5LDIuNjQsMC45OWMxLjIzLDAsMi4xMS0wLjMzLDIuODEtMC45OQoJCWMwLjctMC44MywxLjA2LTEuNjUsMS4wNi0yLjY1QzI3NC45OSwyMjAuOTksMjc0LjY0LDIyMC4xNiwyNzMuOTMsMjE5LjV6IE0yNzYuMDUsMjA4Ljc1Yy0wLjg4LTAuNjYtMS43Ni0wLjk5LTIuODItMC45OQoJCWMtMS4wNSwwLTEuOTMsMC4zMy0yLjgxLDAuOTljLTAuNywwLjY2LTEuMDYsMS40OS0xLjA2LDIuNDhjMCwxLjE2LDAuMzUsMS45OCwxLjA2LDIuNjVjMC44OCwwLjY2LDEuNzYsMC45OSwyLjgxLDAuOTkKCQljMS4wNiwwLDEuOTQtMC4zMywyLjgyLTAuOTljMC43LTAuNjYsMS4wNi0xLjQ5LDEuMDYtMi42NUMyNzcuMSwyMTAuMjQsMjc2Ljc1LDIwOS40MSwyNzYuMDUsMjA4Ljc1eiBNMjc2LjQsMTk3LjAxCgkJYy0wLjctMC42Ni0xLjc2LTAuOTktMi44Mi0wLjk5cy0xLjkzLDAuMzMtMi42NCwwLjk5Yy0wLjg4LDAuODMtMS4yMywxLjY1LTEuMjMsMi42NWMwLDAuOTksMC4zNSwxLjgyLDEuMjMsMi42NQoJCWMwLjcsMC42NiwxLjU4LDAuOTksMi42NCwwLjk5czIuMTEtMC4zMywyLjgyLTAuOTljMC43LTAuODMsMS4wNS0xLjY1LDEuMDUtMi42NUMyNzcuNDUsMTk4LjY2LDI3Ny4xLDE5Ny44MywyNzYuNCwxOTcuMDF6Ii8+Cgk8cGF0aCBjbGFzcz0iZmFyZ2UiIGQ9Ik0zNjIuNiwxNTguOTdjNC43NSwwLjE3LDUuMSwwLjE3LDUuNDUsNS42MmMwLjE4LDIuOTgsMC43LDYuOTUsMC43LDEwLjA5cy0wLjM1LDUuNDYtMS45Myw1LjQ2CgkJYy0yLjI5LDAtOC4wOSwwLjMzLTEwLjU2LDAuMzNjLTIuODEsMC01LjgsMC4xNy04Ljc5LDAuMTdjLTcuMzksMC0xNS4xMy0wLjE3LTIyLjg3LTAuMTdjLTIuMjksMC00Ljc1LDAuMzMtNi44NiwwLjMzdjUuMjkKCQljMCwzLjMxLTAuMzUsOC4yNy0wLjM1LDExLjQxYzAsMC42NiwwLjE4LDEuMzIsMC4xOCwxLjgyYzguNjIsMC44MywxNy40MSwxLjMyLDI2LjIxLDEuNjVoMC44OGg0LjRjMi44MiwwLDQuOTMsMC4xNyw1LjEsMS42NQoJCWMwLjM1LDMuMTQsMS41OCw5LjEsMS41OCwxMi41N2MwLDEuNDktMC4zNSwyLjY1LTAuODgsMi42NWMtMC4zNSwwLTMuNTIsMC41LTMuODcsMC41aC0wLjdoLTUuNjNoLTAuNTMKCQljLTguNDUsMC0xOC4xMiwwLTI2LjM5LTEuMTZjLTAuNTMsOS43Ni0wLjUzLDE2LjIxLTAuODgsMjUuOGMxMi4zMSwwLjgzLDIxLjY0LDEuMTYsMzYuNDIsMS44MmgwLjE4YzIuODEsMCw1LjgtMC4zMyw4LjQ0LTAuMzMKCQljMi4yOSwwLDQuNCwwLjE3LDUuOTgsMC44M2MwLjcsMC4zMywwLjcsMi40OCwwLjcsNC40N3YxLjY1YzAuMTcsMi44MSwwLjUzLDYuNDUsMC41Myw5LjQzYzAsMi40OC0wLjM1LDQuNDctMC44OCw0Ljk2CgkJYy0wLjg4LDAuODMtNi4xNiwwLjUtNy41NiwwLjY2Yy0yLjI5LDAuMTYtNC4wNSwwLjMzLTUuOTgsMC4zM2gtNC4yMmgtMi42NGgtNy41N2MtMTYuMTgsMC00My4xLTAuNS00NS4wNC0yLjMxCgkJYy0wLjM1LTAuMzMtMC4zNS0yLjY1LTAuMzUtNi4xMmMwLTEwLjA5LDAuODgtMjkuNzcsMC44OC0zNC4yM3YtMTIuNzNjMC04Ljc2LDAuNTMtMTcuMDMsMC41My0yNS40N3YtMjYuOTYKCQljMC0wLjgzLDQuMjItMS4xNiw4LjgtMS4xNmM0LjIyLDAsOC43OSwwLjE3LDEwLjU2LDAuMTdDMzMwLjA1LDE1Ny45OCwzNDguODgsMTU4LjY0LDM2Mi42LDE1OC45N3ogTTMwNy43MSwyMTguMzQKCQljLTAuNy0wLjgzLTEuNTgtMS4xNi0yLjY0LTEuMTZjLTEuMDYsMC0yLjExLDAuMzMtMi44MiwxLjE2Yy0wLjcsMC42Ni0xLjA2LDEuNDktMS4wNiwyLjQ4czAuMzUsMS44MiwxLjA2LDIuNjUKCQljMC43LDAuNjYsMS43NiwwLjk5LDIuODIsMC45OWMxLjA1LDAsMS45My0wLjMzLDIuNjQtMC45OWMwLjg4LTAuODMsMS4yMy0xLjY1LDEuMjMtMi42NVMzMDguNTksMjE5LDMwNy43MSwyMTguMzR6CgkJIE0zMDguNDIsMTkzLjg2Yy0wLjctMC44My0xLjc2LTEuMTYtMi44Mi0xLjE2Yy0xLjA2LDAtMS45MywwLjMzLTIuNjQsMS4xNmMtMC44OCwwLjY2LTEuMjMsMS40OS0xLjIzLDIuNDgKCQljMCwwLjk5LDAuMzUsMS45OCwxLjIzLDIuNjVjMC43LDAuNjYsMS41OCwwLjk5LDIuNjQsMC45OWMxLjA1LDAsMi4xMS0wLjMzLDIuODItMC45OWMwLjctMC42NiwxLjA1LTEuNjUsMS4wNS0yLjY1CgkJQzMwOS40NywxOTUuMzUsMzA5LjEyLDE5NC41MywzMDguNDIsMTkzLjg2eiBNMzA4LjQyLDIwNy4yNmMtMC43LTAuODMtMS43Ni0xLjE2LTIuODItMS4xNmMtMS4wNiwwLTEuOTMsMC4zMy0yLjY0LDEuMTYKCQljLTAuODgsMC42Ni0xLjIzLDEuNDktMS4yMywyLjQ4YzAsMC45OSwwLjM1LDEuODIsMS4yMywyLjY1YzAuNywwLjY2LDEuNTgsMC45OSwyLjY0LDAuOTljMS4wNSwwLDIuMTEtMC4zMywyLjgyLTAuOTkKCQljMC43LTAuODMsMS4wNS0xLjY1LDEuMDUtMi42NUMzMDkuNDcsMjA4Ljc1LDMwOS4xMiwyMDcuOTIsMzA4LjQyLDIwNy4yNnogTTMwOC40MiwyMjguOTJjLTAuNy0wLjY2LTEuNzYtMC45OS0yLjgyLTAuOTkKCQljLTEuMDYsMC0xLjkzLDAuMzMtMi42NCwwLjk5Yy0wLjg4LDAuNjYtMS4yMywxLjY1LTEuMjMsMi42NWMwLDAuOTksMC4zNSwxLjgyLDEuMjMsMi40OGMwLjcsMC44MywxLjU4LDEuMTYsMi42NCwxLjE2CgkJYzEuMDUsMCwyLjExLTAuMzMsMi44Mi0xLjE2YzAuNy0wLjY2LDEuMDUtMS40OSwxLjA1LTIuNDhDMzA5LjQ3LDIzMC41OCwzMDkuMTIsMjI5LjU4LDMwOC40MiwyMjguOTJ6IE0zMDguNDIsMjM5Ljg0CgkJYy0wLjctMC42Ni0xLjc2LTAuOTktMi44Mi0wLjk5Yy0xLjA2LDAtMS45MywwLjMzLTIuNjQsMC45OWMtMC44OCwwLjY2LTEuMjMsMS40OS0xLjIzLDIuNjRjMCwwLjk5LDAuMzUsMS44MiwxLjIzLDIuNDgKCQljMC43LDAuNjYsMS41OCwxLjE2LDIuNjQsMS4xNmMxLjA1LDAsMi4xMS0wLjUsMi44Mi0xLjE2YzAuNy0wLjY2LDEuMDUtMS40OSwxLjA1LTIuNDhDMzA5LjQ3LDI0MS4zMywzMDkuMTIsMjQwLjUsMzA4LjQyLDIzOS44NHoKCQkgTTMwOC40MiwyNTEuNThjLTAuNy0wLjgzLTEuNzYtMS4xNi0yLjgyLTEuMTZjLTEuMDYsMC0xLjkzLDAuMzMtMi42NCwxLjE2Yy0wLjg4LDAuNjYtMS4yMywxLjQ5LTEuMjMsMi40OAoJCWMwLDAuOTksMC4zNSwxLjgyLDEuMjMsMi42NGMwLjcsMC42NiwxLjU4LDAuOTksMi42NCwwLjk5YzEuMDUsMCwyLjExLTAuMzMsMi44Mi0wLjk5YzAuNy0wLjgzLDEuMDUtMS42NSwxLjA1LTIuNjQKCQlDMzA5LjQ3LDI1My4wNywzMDkuMTIsMjUyLjI0LDMwOC40MiwyNTEuNTh6IE0zMDguNzcsMTY3LjU3Yy0wLjg4LTAuNjYtMS43Ni0wLjk5LTIuODItMC45OWMtMS4wNSwwLTEuOTMsMC4zMy0yLjgxLDAuOTkKCQljLTAuNywwLjgzLTEuMDYsMS42NS0xLjA2LDIuNjVjMCwwLjk5LDAuMzUsMS44MiwxLjA2LDIuNjVjMC44OCwwLjY2LDEuNzYsMC45OSwyLjgxLDAuOTljMS4wNiwwLDEuOTMtMC4zMywyLjgyLTAuOTkKCQljMC43LTAuODMsMS4wNS0xLjY1LDEuMDUtMi42NUMzMDkuODIsMTY5LjIyLDMwOS40NywxNjguNCwzMDguNzcsMTY3LjU3eiBNMzA5LjEyLDE4MC40N2MtMC43LTAuNjYtMS41OC0xLjE2LTIuNjQtMS4xNgoJCWMtMS4wNSwwLTEuOTMsMC41LTIuODEsMS4xNmMtMC43LDAuNjYtMS4wNiwxLjQ5LTEuMDYsMi40OGMwLDEuMTYsMC4zNSwxLjk4LDEuMDYsMi42NWMwLjg4LDAuNjYsMS43NiwwLjk5LDIuODEsMC45OQoJCWMxLjA2LDAsMS45NC0wLjMzLDIuNjQtMC45OWMwLjg4LTAuNjYsMS4yMy0xLjQ5LDEuMjMtMi42NUMzMTAuMzUsMTgxLjk2LDMxMCwxODEuMTMsMzA5LjEyLDE4MC40N3ogTTMyMC4zOCwyNTIuNDEKCQljLTAuNy0wLjY2LTEuNTgtMC45OS0yLjgxLTAuOTljLTEuMDYsMC0xLjkzLDAuMzMtMi42NCwwLjk5Yy0wLjcsMC44My0xLjIzLDEuNjUtMS4yMywyLjY1czAuNTMsMS44MiwxLjIzLDIuNDgKCQljMC43LDAuODMsMS41OCwxLjE2LDIuNjQsMS4xNmMxLjIzLDAsMi4xMS0wLjMzLDIuODEtMS4xNmMwLjctMC42NiwxLjA2LTEuNDksMS4wNi0yLjQ4UzMyMS4wOCwyNTMuMjMsMzIwLjM4LDI1Mi40MXoKCQkgTTMyMS45NiwyMDcuMDljLTAuNy0wLjY2LTEuNTgtMC45OS0yLjY0LTAuOTljLTEuMjMsMC0yLjExLDAuMzMtMi44MiwwLjk5Yy0wLjcsMC42Ni0xLjA1LDEuNDktMS4wNSwyLjQ4CgkJYzAsMS4xNiwwLjM1LDEuOTgsMS4wNSwyLjY1YzAuNywwLjY2LDEuNTgsMC45OSwyLjgyLDAuOTljMS4wNSwwLDEuOTMtMC4zMywyLjY0LTAuOTlzMS4yMy0xLjQ5LDEuMjMtMi42NQoJCUMzMjMuMTksMjA4LjU4LDMyMi42NywyMDcuNzYsMzIxLjk2LDIwNy4wOXogTTMyMy41NSwxNjcuMjRjLTAuODgtMC42Ni0xLjc2LTAuOTktMi44Mi0wLjk5Yy0xLjA1LDAtMS45MywwLjMzLTIuODIsMC45OQoJCWMtMC43LDAuODMtMS4wNSwxLjY1LTEuMDUsMi42NWMwLDAuOTksMC4zNSwxLjgyLDEuMDUsMi42NWMwLjg4LDAuNjYsMS43NiwwLjk5LDIuODIsMC45OWMxLjA1LDAsMS45My0wLjMzLDIuODItMC45OQoJCWMwLjctMC44MywxLjA1LTEuNjUsMS4wNS0yLjY1QzMyNC42LDE2OC44OSwzMjQuMjUsMTY4LjA3LDMyMy41NSwxNjcuMjR6IE0zMzIuNTIsMjUzLjIzYy0wLjctMC42Ni0xLjU4LTAuOTktMi42NC0wLjk5CgkJYy0xLjA1LDAtMi4xMSwwLjMzLTIuODIsMC45OXMtMS4wNSwxLjQ5LTEuMDUsMi40OGMwLDEuMTYsMC4zNSwxLjk4LDEuMDUsMi42NWMwLjcsMC42NiwxLjc2LDAuOTksMi44MiwwLjk5CgkJYzEuMDYsMCwxLjkzLTAuMzMsMi42NC0wLjk5YzAuODgtMC42NiwxLjIzLTEuNDksMS4yMy0yLjY1QzMzMy43NSwyNTQuNzIsMzMzLjQsMjUzLjg5LDMzMi41MiwyNTMuMjN6IE0zMzMuOTIsMjA2Ljc2CgkJYy0wLjctMC44My0xLjU4LTEuMTYtMi42NC0xLjE2cy0yLjExLDAuMzMtMi44MiwxLjE2Yy0wLjcsMC42Ni0xLjA1LDEuNDktMS4wNSwyLjQ4YzAsMC45OSwwLjM1LDEuODIsMS4wNSwyLjY0CgkJYzAuNywwLjY2LDEuNzYsMC45OSwyLjgyLDAuOTlzMS45My0wLjMzLDIuNjQtMC45OWMwLjg4LTAuODMsMS4yMy0xLjY1LDEuMjMtMi42NEMzMzUuMTYsMjA4LjI1LDMzNC44LDIwNy40MywzMzMuOTIsMjA2Ljc2egoJCSBNMzM2LjkyLDE2Ni41OGMtMC44OC0wLjgzLTEuNzYtMS4xNi0yLjgyLTEuMTZjLTEuMDYsMC0xLjkzLDAuMzMtMi44MiwxLjE2Yy0wLjcsMC42Ni0xLjA1LDEuNDktMS4wNSwyLjQ4CgkJYzAsMC45OSwwLjM1LDEuODIsMS4wNSwyLjY1YzAuODgsMC42NiwxLjc2LDAuOTksMi44MiwwLjk5YzEuMDUsMCwxLjkzLTAuMzMsMi44Mi0wLjk5YzAuNy0wLjgzLDEuMDUtMS42NSwxLjA1LTIuNjUKCQlDMzM3Ljk3LDE2OC4wNywzMzcuNjIsMTY3LjI0LDMzNi45MiwxNjYuNTh6IE0zNDYuNDEsMjUzLjA3Yy0wLjg4LTAuNjYtMS43Ni0wLjk5LTIuODItMC45OWMtMS4wNSwwLTEuOTMsMC4zMy0yLjgxLDAuOTkKCQljLTAuNywwLjgzLTEuMDYsMS42NS0xLjA2LDIuNjVjMCwwLjk5LDAuMzUsMS44MiwxLjA2LDIuNjVjMC44OCwwLjY2LDEuNzYsMC45OSwyLjgxLDAuOTljMS4wNiwwLDEuOTMtMC4zMywyLjgyLTAuOTkKCQljMC43LTAuODMsMS4wNi0xLjY1LDEuMDYtMi42NUMzNDcuNDcsMjU0LjcyLDM0Ny4xMiwyNTMuODksMzQ2LjQxLDI1My4wN3ogTTM0Ni45NCwyMDYuOTNjLTAuNy0wLjgzLTEuNTgtMS4xNi0yLjY0LTEuMTYKCQljLTEuMDUsMC0yLjExLDAuMzMtMi44MSwxLjE2Yy0wLjcsMC42Ni0xLjA2LDEuNDktMS4wNiwyLjQ4YzAsMC45OSwwLjM1LDEuOTgsMS4wNiwyLjY1czEuNzYsMC45OSwyLjgxLDAuOTkKCQljMS4wNiwwLDEuOTMtMC4zMywyLjY0LTAuOTljMC44OC0wLjY2LDEuMjMtMS42NSwxLjIzLTIuNjVDMzQ4LjE3LDIwOC40MiwzNDcuODIsMjA3LjU5LDM0Ni45NCwyMDYuOTN6IE0zNDkuMjMsMTY2LjU4CgkJYy0wLjctMC44My0xLjU4LTEuMTYtMi44Mi0xLjE2Yy0xLjA1LDAtMS45MywwLjMzLTIuNjQsMS4xNmMtMC43LDAuNjYtMS4wNSwxLjQ5LTEuMDUsMi40OGMwLDAuOTksMC4zNSwxLjgyLDEuMDUsMi42NQoJCWMwLjcsMC42NiwxLjU4LDAuOTksMi42NCwwLjk5YzEuMjMsMCwyLjExLTAuMzMsMi44Mi0wLjk5YzAuNy0wLjgzLDEuMDUtMS42NSwxLjA1LTIuNjUKCQlDMzUwLjI4LDE2OC4wNywzNDkuOTMsMTY3LjI0LDM0OS4yMywxNjYuNTh6IE0zNTkuMjYsMjUyLjU3Yy0wLjg4LTAuNjYtMS43Ni0wLjk5LTIuODItMC45OXMtMS45MywwLjMzLTIuODIsMC45OQoJCWMtMC43LDAuODMtMS4wNSwxLjY1LTEuMDUsMi42NWMwLDAuOTksMC4zNSwxLjgyLDEuMDUsMi42NWMwLjg4LDAuNjYsMS43NiwwLjk5LDIuODIsMC45OXMxLjkzLTAuMzMsMi44Mi0wLjk5CgkJYzAuNy0wLjgzLDEuMDUtMS42NSwxLjA1LTIuNjVDMzYwLjMxLDI1NC4yMiwzNTkuOTYsMjUzLjQsMzU5LjI2LDI1Mi41N3ogTTM2MS4wMiwxNjUuNzVjLTAuNy0wLjY2LTEuNTgtMC45OS0yLjgyLTAuOTkKCQljLTEuMDUsMC0xLjkzLDAuMzMtMi42NCwwLjk5Yy0wLjcsMC42Ni0xLjIzLDEuNDktMS4yMywyLjY1YzAsMC45OSwwLjUzLDEuODIsMS4yMywyLjQ4YzAuNywwLjY2LDEuNTgsMC45OSwyLjY0LDAuOTkKCQljMS4yMywwLDIuMTEtMC4zMywyLjgyLTAuOTljMC43LTAuNjYsMS4wNi0xLjQ5LDEuMDYtMi40OEMzNjIuMDcsMTY3LjI0LDM2MS43MiwxNjYuNDEsMzYxLjAyLDE2NS43NXoiLz4KCTxwYXRoIGNsYXNzPSJmYXJnZSIgZD0iTTE2MC4wMywyNzguMTljMC01LjQ2LDAuMzUtMTAuMDksMS41OC0xMC41OGMxLjIzLDAsOS4xNSw1LjYyLDEwLjU2LDYuNjJjOC45Nyw2Ljk1LDE3Ljc3LDE0LjIyLDI1LjY4LDIxLjk5CgkJYzUuOTgsNS43OSwxMi42NywxMC41OCwxOC44MiwxNi4yMWM0LjkyLDQuNDcsMTAuMiw4LjYsMTQuNzgsMTMuMjNjNC4yMiw0LjE0LDguNjIsOC4yNywxMi4zMiwxMy4wNgoJCWMwLjE4LTAuODMsMC4xOC0xLjY1LDAuMTgtMi40OGMwLjE3LTEuOTgsMC41My0zLjk3LDAuNy02LjEyYzAuNTMtNS45NSwzLjE3LTQ4LjI5LDQuOTMtNDguMjloMy42OWM1LjEsMCwxMS45NiwwLjMzLDExLjk2LDMuNjQKCQljMCw3LjYxLTAuNTMsMTUuMzgtMC43LDIyLjgyYzAsMC42NiwwLDEuMzIsMCwxLjk4Yy0wLjg4LDIxLjUtMi42NCwzNi43MS0zLjUyLDU3LjM4Yy0wLjE4LDIuOTgsMCw2Ljc4LTEuMDYsOS4yNgoJCWMtMC4zNSwxLjE2LTEuNDEsMS42NS0yLjQ2LDEuNjVjLTAuNywwLTEuNDEtMC4xNy0yLjExLTAuNjZjLTMuNTItMi42NS03LjIxLTUuNzktMTAuMi05LjA5Yy05Ljg1LTEwLjA5LTIxLjQ2LTE4LjM2LTMxLjMxLTI4LjI4CgkJYy01Ljk4LTUuOTUtMTIuNDktMTEuNTgtMTguNDctMTcuNTNjLTMuMTctMy4xNC02LjUxLTUuOTUtOS41LTkuMDljLTAuODgtMC44My0xLjQxLTEuODItMi4yOS0yLjY1CgkJYy0xLjc2LTEuOTgtMC41My0xLjQ5LTIuNjQtMy4xNGMwLjUzLDExLjkxLDEuMDYsMjQuODEsMS4wNiwzNy43MWMwLDQuOC0wLjE4LDkuNDMtMC4xOCwxNC4yMmMwLDEuODIsMC4xOCw0LjMsMC4xOCw2Ljc4CgkJYzAsMy42NCwwLDcuNDQtMC4xOCw5Ljc2YzAsMC42Ni0wLjg4LDAuNjYtMS45MywwLjY2Yy0xLjc2LDAtNC41Ny0wLjUtNi42OS0wLjVoLTAuNTNjLTIuNDYsMC4xNy01Ljk4LDAuNS04LjI3LDAuNQoJCWMtMS41OCwwLTIuODItMC4xNi0yLjgyLTAuNjZsLTAuMzUtOC4yN3YtMi45OGMwLTUuNzksMC4zNS0xMS41OCwwLjM1LTE3LjJjMC0xLjY1LTAuMTgtMy4zMS0wLjE4LTQuOAoJCWMtMC4zNS0xNi44NywwLTMzLjI0LTAuODgtNDkuNDVDMTYwLjM4LDI5MS4wOSwxNjAuMDMsMjg0LjE1LDE2MC4wMywyNzguMTl6IE0xNzEuMjksMjgxLjVjLTAuODgtMC42Ni0xLjc2LTAuOTktMi44MS0wLjk5CgkJYy0xLjA2LDAtMS45NCwwLjMzLTIuODIsMC45OWMtMC43LDAuODMtMS4wNiwxLjY1LTEuMDYsMi42NWMwLDAuOTksMC4zNSwxLjgyLDEuMDYsMi42NWMwLjg4LDAuNjYsMS43NiwwLjk5LDIuODIsMC45OQoJCWMxLjA1LDAsMS45My0wLjMzLDIuODEtMC45OWMwLjctMC44MywxLjA2LTEuNjUsMS4wNi0yLjY1QzE3Mi4zNCwyODMuMTYsMTcxLjk5LDI4Mi4zMywxNzEuMjksMjgxLjV6IE0xNzEuOTksMjkzLjkxCgkJYy0wLjg4LTAuNjYtMS43Ni0wLjk5LTIuODItMC45OWMtMS4wNSwwLTEuOTMsMC4zMy0yLjgxLDAuOTljLTAuNywwLjgzLTEuMDYsMS42NS0xLjA2LDIuNjVzMC4zNSwxLjgyLDEuMDYsMi42NQoJCWMwLjg4LDAuNjYsMS43NiwwLjk5LDIuODEsMC45OWMxLjA2LDAsMS45NC0wLjMzLDIuODItMC45OWMwLjctMC44MywxLjA2LTEuNjUsMS4wNi0yLjY1UzE3Mi42OSwyOTQuNzMsMTcxLjk5LDI5My45MXoKCQkgTTE3Mi41MiwzMDYuNDdjLTAuODgtMC42Ni0xLjc2LTAuOTktMi44MS0wLjk5Yy0xLjA2LDAtMS45MywwLjMzLTIuODIsMC45OWMtMC43LDAuODMtMS4wNiwxLjY1LTEuMDYsMi42NQoJCWMwLDAuOTksMC4zNSwxLjgyLDEuMDYsMi42NGMwLjg4LDAuNjYsMS43NiwwLjk5LDIuODIsMC45OWMxLjA2LDAsMS45My0wLjMzLDIuODEtMC45OWMwLjctMC44MywxLjA2LTEuNjUsMS4wNi0yLjY0CgkJQzE3My41NywzMDguMTMsMTczLjIyLDMwNy4zLDE3Mi41MiwzMDYuNDd6IE0xNzIuNjksMzE4LjA1Yy0wLjctMC42Ni0xLjc2LTAuOTktMi44Mi0wLjk5Yy0xLjA2LDAtMS45MywwLjMzLTIuNjQsMC45OQoJCWMtMC44OCwwLjY2LTEuMjMsMS40OS0xLjIzLDIuNjVjMCwwLjk5LDAuMzUsMS44MiwxLjIzLDIuNDhjMC43LDAuNjYsMS41OCwwLjk5LDIuNjQsMC45OWMxLjA2LDAsMi4xMS0wLjMzLDIuODItMC45OQoJCWMwLjctMC42NiwxLjA2LTEuNDksMS4wNi0yLjQ4QzE3My43NSwzMTkuNTQsMTczLjQsMzE4LjcxLDE3Mi42OSwzMTguMDV6IE0xNzMuMjIsMzMwLjEyYy0wLjctMC42Ni0xLjU4LTAuOTktMi44MS0wLjk5CgkJYy0xLjA2LDAtMS45MywwLjMzLTIuNjQsMC45OWMtMC43LDAuODMtMS4yMywxLjY1LTEuMjMsMi42NWMwLDAuOTksMC41MywxLjgyLDEuMjMsMi42NWMwLjcsMC42NiwxLjU4LDAuOTksMi42NCwwLjk5CgkJYzEuMjMsMCwyLjExLTAuMzMsMi44MS0wLjk5YzAuNy0wLjgzLDEuMDYtMS42NSwxLjA2LTIuNjVDMTc0LjI4LDMzMS43NywxNzMuOTIsMzMwLjk1LDE3My4yMiwzMzAuMTJ6IE0xNzMuMDUsMzQzLjAyCgkJYy0wLjctMC42Ni0xLjU4LTAuOTktMi42NC0wLjk5Yy0xLjA2LDAtMi4xMSwwLjMzLTIuODIsMC45OWMtMC43LDAuNjYtMS4wNiwxLjY1LTEuMDYsMi42NXMwLjM1LDEuODIsMS4wNiwyLjQ4CgkJYzAuNywwLjgzLDEuNzYsMS4xNiwyLjgyLDEuMTZjMS4wNSwwLDEuOTMtMC4zMywyLjY0LTEuMTZjMC44OC0wLjY2LDEuMjMtMS40OSwxLjIzLTIuNDhTMTczLjkyLDM0My42OCwxNzMuMDUsMzQzLjAyegoJCSBNMTczLjIyLDM1NC43NmMtMC43LTAuNjYtMS41OC0wLjk5LTIuNjQtMC45OXMtMi4xMSwwLjMzLTIuODEsMC45OWMtMC43LDAuODMtMS4wNiwxLjY1LTEuMDYsMi42NWMwLDAuOTksMC4zNSwxLjgyLDEuMDYsMi42NQoJCWMwLjcsMC42NiwxLjc2LDAuOTksMi44MSwwLjk5czEuOTMtMC4zMywyLjY0LTAuOTljMC44OC0wLjgzLDEuMjMtMS42NSwxLjIzLTIuNjVDMTc0LjQ1LDM1Ni40MiwxNzQuMSwzNTUuNTksMTczLjIyLDM1NC43NnoKCQkgTTE3My41NywzNjUuNTFjLTAuODgtMC42Ni0xLjc2LTAuOTktMi44MS0wLjk5Yy0xLjA2LDAtMS45NCwwLjMzLTIuODIsMC45OWMtMC43LDAuNjYtMS4wNiwxLjQ5LTEuMDYsMi42NQoJCWMwLDAuOTksMC4zNSwxLjgyLDEuMDYsMi40OGMwLjg4LDAuNjYsMS43NiwxLjE2LDIuODIsMS4xNmMxLjA1LDAsMS45My0wLjUsMi44MS0xLjE2YzAuNy0wLjY2LDEuMDYtMS40OSwxLjA2LTIuNDgKCQlDMTc0LjYzLDM2NywxNzQuMjgsMzY2LjE3LDE3My41NywzNjUuNTF6IE0xODIuMTksMjg5Ljc3Yy0wLjctMC42Ni0xLjU4LTEuMTYtMi44MS0xLjE2Yy0xLjA2LDAtMS45NCwwLjUtMi42NCwxLjE2CgkJYy0wLjcsMC42Ni0xLjIzLDEuNDktMS4yMywyLjQ4YzAsMS4xNiwwLjUzLDEuOTgsMS4yMywyLjY1YzAuNywwLjY2LDEuNTgsMC45OSwyLjY0LDAuOTljMS4yMywwLDIuMTEtMC4zMywyLjgxLTAuOTkKCQljMC43LTAuNjYsMS4wNi0xLjQ5LDEuMDYtMi42NUMxODMuMjUsMjkxLjI2LDE4Mi45LDI5MC40MywxODIuMTksMjg5Ljc3eiBNMTkwLjQ2LDI5OC4yYy0wLjctMC42Ni0xLjU4LTAuOTktMi42NC0wLjk5CgkJYy0xLjIzLDAtMi4xMSwwLjMzLTIuODEsMC45OWMtMC43LDAuODMtMS4wNiwxLjY1LTEuMDYsMi42NWMwLDAuOTksMC4zNSwxLjgyLDEuMDYsMi42NWMwLjcsMC42NiwxLjU4LDAuOTksMi44MSwwLjk5CgkJYzEuMDYsMCwxLjkzLTAuMzMsMi42NC0wLjk5YzAuNy0wLjgzLDEuMjMtMS42NSwxLjIzLTIuNjVDMTkxLjY5LDI5OS44NiwxOTEuMTYsMjk5LjAzLDE5MC40NiwyOTguMnogTTE5OS45NiwzMDcuMTQKCQljLTAuNy0wLjgzLTEuNTgtMS4xNi0yLjY0LTEuMTZjLTEuMDUsMC0yLjExLDAuMzMtMi44MSwxLjE2Yy0wLjcsMC42Ni0xLjA2LDEuNDktMS4wNiwyLjQ4czAuMzUsMS45OCwxLjA2LDIuNjUKCQljMC43LDAuNjYsMS43NiwwLjk5LDIuODEsMC45OWMxLjA2LDAsMS45NC0wLjMzLDIuNjQtMC45OWMwLjg4LTAuNjYsMS4yMy0xLjY1LDEuMjMtMi42NVMyMDAuODQsMzA3LjgsMTk5Ljk2LDMwNy4xNHoKCQkgTTIwOS44MSwzMTQuOTFjLTAuNy0wLjY2LTEuNzYtMC45OS0yLjgyLTAuOTljLTEuMDYsMC0xLjkzLDAuMzMtMi42NCwwLjk5Yy0wLjg4LDAuNjYtMS4yMywxLjQ5LTEuMjMsMi42NQoJCWMwLDAuOTksMC4zNSwxLjgyLDEuMjMsMi40OGMwLjcsMC42NiwxLjU4LDEuMTYsMi42NCwxLjE2YzEuMDYsMCwyLjExLTAuNSwyLjgyLTEuMTZjMC43LTAuNjYsMS4wNS0xLjQ5LDEuMDUtMi40OAoJCUMyMTAuODcsMzE2LjQsMjEwLjUyLDMxNS41NywyMDkuODEsMzE0LjkxeiBNMjE4LjA4LDMyMi44NWMtMC43LTAuNjYtMS41OC0wLjk5LTIuNjQtMC45OWMtMS4yMywwLTIuMTEsMC4zMy0yLjgyLDAuOTkKCQljLTAuNywwLjgzLTEuMDYsMS42NS0xLjA2LDIuNjRjMCwwLjk5LDAuMzUsMS44MiwxLjA2LDIuNjVjMC43LDAuNjYsMS41OCwwLjk5LDIuODIsMC45OWMxLjA1LDAsMS45My0wLjMzLDIuNjQtMC45OQoJCWMwLjctMC44MywxLjIzLTEuNjUsMS4yMy0yLjY1QzIxOS4zMSwzMjQuNSwyMTguNzgsMzIzLjY3LDIxOC4wOCwzMjIuODV6IE0yMjguMjgsMzMyLjExYy0wLjctMC42Ni0xLjU4LTAuOTktMi44Mi0wLjk5CgkJYy0xLjA1LDAtMS45MywwLjMzLTIuNjQsMC45OWMtMC43LDAuNjYtMS4yMywxLjQ5LTEuMjMsMi42NWMwLDAuOTksMC41MywxLjgyLDEuMjMsMi40OGMwLjcsMC42NiwxLjU4LDAuOTksMi42NCwwLjk5CgkJYzEuMjMsMCwyLjExLTAuMzMsMi44Mi0wLjk5YzAuNy0wLjY2LDEuMDYtMS40OSwxLjA2LTIuNDhDMjI5LjM0LDMzMy41OSwyMjguOTksMzMyLjc3LDIyOC4yOCwzMzIuMTF6IE0yMzUuMzIsMzQxLjA0CgkJYy0wLjctMC42Ni0xLjU4LTAuOTktMi42NC0wLjk5Yy0xLjA2LDAtMi4xMSwwLjMzLTIuODIsMC45OWMtMC43LDAuNjYtMS4wNiwxLjY1LTEuMDYsMi42NWMwLDAuOTksMC4zNSwxLjgyLDEuMDYsMi40OAoJCWMwLjcsMC44MywxLjc2LDEuMTYsMi44MiwxLjE2YzEuMDUsMCwxLjkzLTAuMzMsMi42NC0xLjE2YzAuODgtMC42NiwxLjIzLTEuNDksMS4yMy0yLjQ4QzIzNi41NSwzNDIuNjksMjM2LjIsMzQxLjcsMjM1LjMyLDM0MS4wNAoJCXogTTI0My43NiwzNTAuNDZjLTAuNy0wLjY2LTEuNzYtMS4xNi0yLjgyLTEuMTZzLTEuOTMsMC41LTIuNjQsMS4xNmMtMC44OCwwLjY2LTEuMjMsMS40OS0xLjIzLDIuNDhjMCwxLjE2LDAuMzUsMS45OCwxLjIzLDIuNjUKCQljMC43LDAuNjYsMS41OCwwLjk5LDIuNjQsMC45OXMyLjExLTAuMzMsMi44Mi0wLjk5YzAuNy0wLjY2LDEuMDUtMS40OSwxLjA1LTIuNjVDMjQ0LjgyLDM1MS45NSwyNDQuNDcsMzUxLjEyLDI0My43NiwzNTAuNDZ6CgkJIE0yNTQuNSwzNjEuMDVjLTAuNy0wLjY2LTEuNTgtMC45OS0yLjgyLTAuOTljLTEuMDYsMC0xLjkzLDAuMzMtMi42NCwwLjk5Yy0wLjcsMC44My0xLjIzLDEuNjUtMS4yMywyLjY1czAuNTMsMS44MiwxLjIzLDIuNjUKCQljMC43LDAuNjYsMS41OCwwLjk5LDIuNjQsMC45OWMxLjIzLDAsMi4xMS0wLjMzLDIuODItMC45OWMwLjctMC44MywxLjA1LTEuNjUsMS4wNS0yLjY1UzI1NS4yLDM2MS44NywyNTQuNSwzNjEuMDV6CgkJIE0yNTUuNTUsMzUwLjEzYy0wLjg4LTAuNjYtMS43Ni0wLjk5LTIuODItMC45OWMtMS4wNSwwLTEuOTMsMC4zMy0yLjgxLDAuOTljLTAuNywwLjgzLTEuMDYsMS42NS0xLjA2LDIuNjUKCQljMCwwLjk5LDAuMzUsMS44MiwxLjA2LDIuNjRjMC44OCwwLjY2LDEuNzYsMC45OSwyLjgxLDAuOTljMS4wNiwwLDEuOTMtMC4zMywyLjgyLTAuOTljMC43LTAuODMsMS4wNS0xLjY1LDEuMDUtMi42NAoJCUMyNTYuNjEsMzUxLjc4LDI1Ni4yNSwzNTAuOTYsMjU1LjU1LDM1MC4xM3ogTTI1Ni40MywzMzguMjJjLTAuNy0wLjY2LTEuNTgtMS4xNi0yLjgyLTEuMTZjLTEuMDUsMC0xLjkzLDAuNS0yLjY0LDEuMTYKCQljLTAuNywwLjY2LTEuMjMsMS40OS0xLjIzLDIuNDhjMCwxLjE2LDAuNTMsMS45OCwxLjIzLDIuNjVjMC43LDAuNjYsMS41OCwwLjk5LDIuNjQsMC45OWMxLjIzLDAsMi4xMS0wLjMzLDIuODItMC45OQoJCWMwLjctMC42NiwxLjA1LTEuNDksMS4wNS0yLjY1QzI1Ny40OSwzMzkuNzEsMjU3LjEzLDMzOC44OSwyNTYuNDMsMzM4LjIyeiBNMjU3LjEzLDMyNi4zMmMtMC44OC0wLjY2LTEuNzYtMC45OS0yLjgyLTAuOTkKCQljLTEuMDYsMC0xLjkzLDAuMzMtMi44MiwwLjk5Yy0wLjcsMC44My0xLjA1LDEuNjUtMS4wNSwyLjY1YzAsMC45OSwwLjM1LDEuODIsMS4wNSwyLjY1YzAuODgsMC42NiwxLjc2LDAuOTksMi44MiwwLjk5CgkJYzEuMDUsMCwxLjkzLTAuMzMsMi44Mi0wLjk5YzAuNy0wLjgzLDEuMDUtMS42NSwxLjA1LTIuNjVDMjU4LjE5LDMyNy45NywyNTcuODQsMzI3LjE1LDI1Ny4xMywzMjYuMzJ6IE0yNTcuMzEsMzEzLjA5CgkJYy0wLjg4LTAuNjYtMS43Ni0wLjk5LTIuODEtMC45OWMtMS4wNiwwLTEuOTMsMC4zMy0yLjgyLDAuOTljLTAuNywwLjgzLTEuMDYsMS42NS0xLjA2LDIuNjVjMCwwLjk5LDAuMzUsMS44MiwxLjA2LDIuNjUKCQljMC44OCwwLjY2LDEuNzYsMC45OSwyLjgyLDAuOTljMS4wNSwwLDEuOTMtMC4zMywyLjgxLTAuOTljMC43LTAuODMsMS4wNi0xLjY1LDEuMDYtMi42NQoJCUMyNTguMzcsMzE0Ljc0LDI1OC4wMSwzMTMuOTIsMjU3LjMxLDMxMy4wOXogTTI1OC4xOSwzMDAuODVjLTAuODgtMC42Ni0xLjc2LTAuOTktMi44Mi0wLjk5Yy0xLjA1LDAtMS45MywwLjMzLTIuODEsMC45OQoJCWMtMC43LDAuODMtMS4wNiwxLjY1LTEuMDYsMi42NWMwLDAuOTksMC4zNSwxLjgyLDEuMDYsMi42NWMwLjg4LDAuNjYsMS43NiwwLjk5LDIuODEsMC45OWMxLjA2LDAsMS45My0wLjMzLDIuODItMC45OQoJCWMwLjctMC44MywxLjA1LTEuNjUsMS4wNS0yLjY1QzI1OS4yNCwzMDIuNSwyNTguODksMzAxLjY4LDI1OC4xOSwzMDAuODV6IE0yNTkuMjQsMjg4LjEyYy0wLjg4LTAuNjYtMS43Ni0wLjk5LTIuODEtMC45OQoJCWMtMS4wNiwwLTEuOTMsMC4zMy0yLjgyLDAuOTljLTAuNywwLjgzLTEuMDUsMS42NS0xLjA1LDIuNjVjMCwwLjk5LDAuMzUsMS44MiwxLjA1LDIuNjVjMC44OCwwLjY2LDEuNzYsMC45OSwyLjgyLDAuOTkKCQljMS4wNSwwLDEuOTMtMC4zMywyLjgxLTAuOTljMC43LTAuODMsMS4wNi0xLjY1LDEuMDYtMi42NUMyNjAuMywyODkuNzcsMjU5Ljk1LDI4OC45NCwyNTkuMjQsMjg4LjEyeiIvPgo8L2c+CjxnPgoJPHBhdGggY2xhc3M9ImZhcmdlIiBkPSJNOTcuOTYsMTAxLjE3YzAsMC42Mi0wLjEyLDEuMzYtMC4xMiwyLjIydjAuNzRjMCwxLjExLTAuMDYsMi4yOC0wLjA2LDIuMjhjLTAuOCwwLTIuMSwwLjA2LTIuOSwwLjA2CgkJYy0xLjQ4LDAuMDYtMy4wMiwwLjE4LTQuNTEsMC4yNWMtMS45MSwwLjEyLTQuOTQsMC4wNi02LjY2LDAuNDljLTAuMTIsMS42LTAuMTksMi45LTAuMTksNC4xM3YzLjUydjAuODYKCQljMCwwLjI1LDAuMDYsMC40OSwwLjEyLDAuNzRjMC45OS0wLjEyLDIuMDQtMC4xOCwzLjE1LTAuMThoMC40OWgxLjExYzIuMjgsMCw1LjgsMCw3LjQ3LDAuMDZoMC4zN2MwLjQ5LDAsMC44LTAuMDYsMS4wNS0wLjA2CgkJYzAuMzcsMCwwLjQzLDAuMTIsMC40MywwLjU2YzAsMC4yNS0wLjA2LDAuNjgtMC4wNiwxLjNjMCwxLjc5LTAuMzcsNS40OS0wLjgsNS40OWMtMC4zNy0wLjA2LTAuOC0wLjA2LTEuMy0wLjA2CgkJYy0wLjY4LDAtMS40OCwwLjA2LTIuMSwwLjA2SDkyLjljLTAuNTYsMC0xLjE3LTAuMDYtMS43My0wLjA2Yy0xLjExLDAtMi4yMiwwLjA2LTMuMzksMC4wNmMtMS4xMSwwLTIuMjItMC4wNi0zLjI3LTAuMTkKCQljMCwzLjI3LDAuMTIsNC41NywwLjEyLDcuNzhjMCwwLjYyLTAuMDYsMS4yMy0wLjA2LDIuMDRjNC4yLDAsMTAuMjUsMC4zNywxNC41LDAuMzdjMC40MywwLDAuNDksMC43NCwwLjQ5LDIuMTZ2MC45OXYxLjIzCgkJYzAsMS43My0wLjEyLDMuNzYtMC45MywzLjc2Yy0wLjU2LTAuMDYtMS4yMy0wLjA2LTEuODUtMC4wNmMtMC45OSwwLTIuMDQsMC4wNi0zLjAyLDAuMDZjLTAuNjIsMC0xLjIzLDAtMS43OS0wLjA2aC0xLjA1CgkJYy0yLjEsMC00LjE0LDAuMTktNi4xNywwLjE5Yy0wLjc0LDAtMS41NC0wLjA2LTIuMjgtMC4xMmMtMS40Mi0wLjEyLTMuODksMC4xOS0zLjk1LTEuMTFjLTAuNjItOC4zOS0wLjgtMTYuNTQtMS43My0yNQoJCWMtMC4zMS0yLjktMC40OS01LjQ5LTAuNDktOC4zM2MwLTEuNjcsMC0zLjMzLDAuMTItNS4xOGMtMC4wNi0wLjEyLTAuMDYtMC4yNS0wLjA2LTAuMzdjMC0wLjM3LDAuMjUtMC44LDAuNjgtMC44NgoJCWMwLDAsMTcuMDMtMC44NiwxOS44MS0wLjg2aDAuMThDOTcuNzgsMTAwLjA2LDk3Ljk2LDEwMC40OSw5Ny45NiwxMDEuMTd6Ii8+Cgk8cGF0aCBjbGFzcz0iZmFyZ2UiIGQ9Ik0xMDcuNDEsMTQyLjUyaC0xLjNjLTEuNTQsMC0zLjAyLTAuMDYtMy4wMi0wLjc0di00LjE0YzAtMC44NiwwLjA2LTEuNjcsMC4wNi0yLjUzdi0yLjU5CgkJYzAtMS41NCwwLTMuMTUsMC4xMi00LjY5YzAuMzctNC45NCwwLjU2LTEwLjA2LDAuOTMtMTUuMTJjMC4xMi0xLjg1LDAuNTYtMTEuNDIsMS4wNS0xMi40YzAuMTktMC4xOSwwLjYyLTAuMjUsMS4yMy0wLjI1CgkJYzEuNjcsMCw0LjMyLDAuNTYsNC42MywwLjg2YzAuMTksMC4xOSwwLjI1LDEuMTEsMC4xOSwxLjZjLTAuMTksMS42Ny0wLjI1LDMuMzMtMC4yNSw1LjA2YzAsMS41NCwwLjA2LDMuMTUsMC4wNiw0LjY5djguMjEKCQljMS4xNy0wLjgsMi45LTIuMjgsMy44My0zLjIxYzQuNjMtNS4yNSw4LjU4LTkuNzUsMTIuNDctMTMuN2MwLjg2LTEuMTEsMS4zNi0xLjYsMS43My0xLjZjMC4xOCwwLDAuMzEsMC4wNiwwLjQ5LDAuMjUKCQljMCwwLDUuNjgsMi4yMiw1LjY4LDIuNDdjMCwwLjU2LTQuOTQsNi4xNy01LjI1LDYuNjdjLTMuMTUsMy44My02LjQ4LDcuNjUtMTEuMjMsMTIuNTljMi43OCwxLjkxLDUuNjIsNC42OSwxMC4zMSw4LjUyCgkJYzEuMTEsMC42OCw1LjA2LDQuMjYsNS45Miw1LjEyYzAuMTIsMC4xMiwwLjEyLDAuMjUsMC4xMiwwLjM3YzAsMC42Mi0wLjY4LDEuNTQtMS4wNSwyLjM0Yy0wLjQzLDAuOTktMS4zLDIuNjUtMi4zNCwzLjAyCgkJYy0xLjIzLTAuMDYtMi4xLTEuMjMtMy4wMi0xLjg1Yy01Ljg2LTQuMzItMTEuNzktOC42NC0xNy42NS0xMy41MnY0LjUxdjkuODFjMCwwLjMxLDAsMC4zNy0wLjE5LDAuMzdjLTAuMTksMC0wLjY4LTAuMTItMS45MS0wLjEyCgkJSDEwNy40MXoiLz4KCTxwYXRoIGNsYXNzPSJmYXJnZSIgZD0iTTE0OC43NywxMzUuOThjMi44NCwwLDQuMzItMS40OCw0LjMyLTMuNDZjMC0xLjQ4LTAuODYtMy4xNS0yLjcyLTQuNjNjLTEuMy0wLjk5LTMuNzctMi41My01LTMuNjQKCQljLTAuOC0wLjQ5LTEuNDItMS4xMS0yLjA0LTEuNzNjLTEuMTEtMS4wNS0yLjUzLTIuMDQtMy4zOS0zLjM5Yy0xLjU0LTIuNTMtMi4xNi00Ljk0LTIuMTYtNy4yMmMwLTAuMzctMC4wNi0wLjY4LDAtMS4wNQoJCWMwLjQ5LTUuMzcsNC43NS05LjUsOS42My0xMC4yNGMwLjMxLTAuMDYsMC42OC0wLjEyLDEuMTEtMC4xMmMyLjY1LDAsNy4zNCwxLjYsOS4wMSwzLjMzYzAuMTIsMC4xMiwwLjE5LDAuNDMsMC4xOSwwLjgKCQljMCwwLjY4LTAuMTksMS43My0wLjQzLDIuNzJjLTAuNDMsMS42LTAuOTMsMi44NC0xLjQ4LDIuODRjLTEuNDItMS4xMS0zLjQ2LTEuNzMtNS4zMS0xLjczYy0yLjY1LDAtNS4wNiwxLjM2LTUuMDYsMy43NgoJCWMwLDAuNjIsMC4xOSwxLjMsMC40OSwyLjA0YzAuOCwxLjM2LDEuNiwyLjM0LDIuODQsMy4yN2MxLjkxLDEuMTEsNC44OCwyLjc4LDYuNDIsNC4yYzAuODYsMC44LDEuNzMsMS42LDIuNDcsMi41OQoJCWMxLjQ4LDIuMTYsMi4xNiw0Ljg4LDIuMTYsNy40N2MwLDMuMzktMS4xNyw2LjczLTMuMzMsOC43NmMtMS43OSwxLjY3LTQuOTQsMi41My04LjI3LDIuNTNjLTAuMTksMC0wLjQzLDAtMC42MiwwCgkJYy01LjU1LTAuMTktMTEuNDgtNC41MS0xMS40OC03LjljMC0wLjc0LDEuNzktNC44OCwyLjg0LTUuMThjMC4zNywwLjA2LDAuODYsMS4wNSwxLjA1LDEuMjNjMi4wNCwxLjkxLDMuMzMsMy4zOSw1LjgsNC4zMgoJCUMxNDYuOTEsMTM1Ljg1LDE0Ny45LDEzNS45OCwxNDguNzcsMTM1Ljk4eiIvPgoJPHBhdGggY2xhc3M9ImZhcmdlIiBkPSJNMTYzLjQ2LDEyMS41M2MwLTIuNDEsMC4xOS00Ljc1LDAuMTktNy4yMnYtMS4yM2MwLTEuNDIsMC4wNi0yLjksMC4xMi00LjMydi0xLjQyYzAtMS4xNy0wLjA2LTIuNTMtMC4wNi0zLjU4CgkJYzAtMS4zLDAuMTItMi4yMiwwLjQzLTIuMjJoMS40MmM3Ljg0LDAsMTYuMjksMS42LDIyLjQ2LDIuNDFjMC45MywwLjA2LDEuMywwLjEyLDEuMzYsMS4xMWMwLjA2LDEuNTQsMC4wNiwyLjk2LDAuMDYsNC41MXYyLjE2CgkJYzAsMC42MiwwLjA2LDEuMTcsMC4wNiwxLjc5bDAuNDMsMTEuNzljMC4wNiwyLjUzLDAuMTksNSwwLjI1LDcuNTljMC4xOSwxLjA1LDAuMTksNC41MSwwLjE5LDUuNjhjLTAuMDYsMC44LDAuMDYsMS42LTAuMTIsMS43OQoJCWMtMC42OCwwLjYyLTIuMSwwLjQzLTMuMjcsMC40OWMtMC40MywwLTAuODYsMC4wNi0xLjMsMC4wNmMtMS40MiwwLTIuMjItMC4yNS0yLjIyLTAuOTljMC0zLjU4LTAuMzctOS41LTAuNDMtMTIuOTYKCQljMC0wLjQ5LTAuMTItMC45My0wLjE4LTEuNDJoLTAuNDloLTEuODVoLTEuNDJjLTEuNzktMC4wNi0zLjU4LTAuMjUtNS40My0wLjI1Yy0wLjg2LDAtMS43MywwLjA2LTIuNTksMC4xOQoJCWMtMC4wNiwwLTAuMTgsMC0wLjI1LDAuMDZjMCwwLjA2LTAuMDYsMC4yNS0wLjEyLDAuMjVjMC4wNiwxLjczLDAuMDYsNC4zOCwwLjA2LDcuMXY5LjMyYzAsMC41Ni0wLjI1LDAuNjgtMC44LDAuNjgKCQljLTAuMzEsMC0wLjgtMC4wNi0xLjM2LTAuMDZjLTEuNzksMC00LjgxLTAuMTgtNC44MS0wLjc0QzE2My43NywxMzYuMSwxNjMuNDYsMTI3LjY0LDE2My40NiwxMjEuNTN6IE0xNzMuMzMsMTE4LjI2aDIuMTZoMC4wNgoJCWMxLjA1LDAsMi4xLDAuMDYsMy4wOSwwLjA2aDAuNDloMC44aDAuNDljMC40OSwwLDAuNjgsMCwxLjY3LDAuMTh2LTAuOTl2LTEuNjd2LTMuMTV2LTEuNzljMC0wLjM3LTAuMDYtMC43NC0wLjA2LTEuMTFoLTAuOTMKCQljLTEuNDgtMC4xMi0yLjcyLTAuMTItMy44OS0wLjEyYy0yLjA0LDAtMy44MywwLjEyLTYuMzYsMC4xOHYwLjY4djEuNzN2Mi4zNGMwLDEuMTcsMCwyLjM1LDAuMDYsMy41MgoJCWMwLjE4LDAuMDYsMC4zMSwwLjA2LDAuNDksMC4wNnYwLjA2aDAuMTJDMTcyLjA0LDExOC4zMiwxNzIuNjUsMTE4LjI2LDE3My4zMywxMTguMjZ6Ii8+Cgk8cGF0aCBjbGFzcz0iZmFyZ2UiIGQ9Ik0yMzYuNDcsMTA2Ljk3YzAsMC42MiwwLDEuMTcsMCwxLjU0YzAsMC43NCwwLDEuMzYsMC4wNiwyLjFjMCwwLjU2LDAuMDYsMS4xNywwLjA2LDEuNzMKCQljMC4xMiwyLjUzLDAuMTksNS4xMiwwLjE5LDcuNzhjMCw1LjEyLTAuMjUsMTAuMTgtMC4yNSwxNS4xOGMwLDEuMjMsMCwyLjQ3LDAuMDYsMy43YzAsMC40OSwwLDEuMDUsMCwxLjU0CgkJYzAsMC42MiwwLDEuMTEtMC4xOSwxLjExaC0zLjM5Yy0wLjM3LDAtMC42Mi0wLjA2LTAuOTktMC4wNmMtMC40MywwLTAuODYsMC0xLjMsMGMtMC44LDAtMS40OC0wLjA2LTEuNDgtMC4zN3YtMTkuOTMKCQljMC0xLjIzLTAuMDYtMi40Ny0wLjA2LTMuN3YtMi4yMmMtMS4xMSwxLjExLTIuMjIsMi4yMi0zLjQsMy4yN2MtMC41NSwwLjQ5LTEuMTcsMC44Ni0xLjczLDEuMzZjLTIuMDQsMS41NC0zLjc2LDMuNTItNi4wNSw0LjgxCgkJYy0wLjM3LDAuMTgtMC42OCwwLjQ5LTEuMTEsMC40OWMtMC42MiwwLTAuOC0wLjI1LTEuMjMtMC40OWMtMS45MS0xLjExLTMuOTUtMi43OC01LjgtNC4yNmwtMi4xNi0xLjY3CgkJYy0xLjc5LTEuMTEtNC4zOC0yLjc4LTYuMjMtNC4xNHYzLjI3YzAsMS41NCwwLDMuMjEtMC4wNiw0Ljc1YzAsMC42Mi0wLjA2LDEuMzYtMC4wNiwxLjk3djEwLjA2YzAsMC43NCwwLjA2LDEuMzYsMC4wNiwyLjEKCQljMCwwLjg2LDAuMTIsMi4xNiwwLjEyLDMuMzNjMCwwLjY4LTAuMDYsMS4zLTAuMTksMS43M2MtMC4zNywwLjM3LTEuNjcsMC41Ni0yLjQxLDAuNTZoLTEuNjdjLTAuNzQsMC0zLjMzLTAuMTItMy4zMy0wLjM3CgkJYzAtMS4yMywwLjA2LTIuNjUsMC4wNi00LjAxYzAuMDYtMS4zLDAuMTktMi41MywwLjE5LTMuODN2LTEzLjc2YzAtMC41NiwwLjA2LTEuMjMsMC4wNi0xLjc5YzAtMC45MywwLTEuNzMsMC0yLjY1CgkJYzAtMC40MywwLTAuODYsMC0xLjNjMC0wLjU2LDAtMS4zLDAuMDYtMS44NWMwLTMuMDIsMC4wNi05LjM4LDAuOTktMTAuNDljMC4wNi0wLjA2LDAuMTItMC4xMiwwLjI1LTAuMTIKCQljMS40OCwwLDYuNTQsNC43NSw3LjIyLDUuMjVjMS40OCwxLjA1LDIuOSwyLjE2LDQuMzgsMy4wOWMzLjAyLDIuMDQsNS41NSw0LjUxLDguNTgsNi40OGMwLjY4LTAuNjgsMS43OS0xLjIzLDIuNDctMS45MQoJCWMxLjE3LTEuMTcsMi41My0yLjE2LDMuNy0zLjMzYzAuOTMtMC44NiwxLjk4LTEuNiwyLjk2LTIuNDFjMC40OS0wLjM3LDAuOTMtMC44NiwxLjQyLTEuMjNjMS44NS0xLjU0LDMuNTgtMy4wMiw1LjU1LTQuNDQKCQljMC44LTAuNTYsMS45MS0xLjczLDMuODktMi4yMkMyMzYuNDEsMTAxLjYsMjM2LjQ3LDEwNC43NSwyMzYuNDcsMTA2Ljk3eiIvPgoJPHBhdGggY2xhc3M9ImZhcmdlIiBkPSJNMjY0LjUsMTAwLjYxYzEuNjcsMC4wNiwxLjc5LDAuMDYsMS45MSwyLjFjMC4wNiwxLjExLDAuMjUsMi41OSwwLjI1LDMuNzdjMCwxLjE3LTAuMTIsMi4wNC0wLjY4LDIuMDQKCQljLTAuOCwwLTIuODQsMC4xMi0zLjcsMC4xMmMtMC45OSwwLTIuMDQsMC4wNi0zLjA4LDAuMDZjLTIuNTksMC01LjMxLTAuMDYtOC4wMi0wLjA2Yy0wLjgsMC0xLjY3LDAuMTItMi40MSwwLjEydjEuOTcKCQljMCwxLjIzLTAuMTIsMy4wOS0wLjEyLDQuMjZjMCwwLjI1LDAuMDYsMC40OSwwLjA2LDAuNjhjMy4wMiwwLjMxLDYuMTEsMC40OSw5LjIsMC42MmgwLjMxaDEuNTRjMC45OSwwLDEuNzMsMC4wNiwxLjc5LDAuNjIKCQljMC4xMiwxLjE3LDAuNTUsMy4zOSwwLjU1LDQuNjljMCwwLjU2LTAuMTIsMC45OS0wLjMxLDAuOTljLTAuMTIsMC0xLjIzLDAuMTktMS4zNiwwLjE5aC0wLjI1aC0xLjk4aC0wLjE4CgkJYy0yLjk2LDAtNi4zNiwwLTkuMjYtMC40M2MtMC4xOSwzLjY0LTAuMTksNi4wNS0wLjMxLDkuNjNjNC4zMiwwLjMxLDcuNTksMC40MywxMi43NywwLjY4aDAuMDZjMC45OSwwLDIuMDQtMC4xMiwyLjk2LTAuMTIKCQljMC44LDAsMS41NCwwLjA2LDIuMSwwLjMxYzAuMjUsMC4xMiwwLjI1LDAuOTMsMC4yNSwxLjY3djAuNjJjMC4wNiwxLjA1LDAuMTksMi40MSwwLjE5LDMuNTJjMCwwLjkzLTAuMTIsMS42Ny0wLjMxLDEuODUKCQljLTAuMzEsMC4zMS0yLjE2LDAuMTktMi42NSwwLjI1Yy0wLjgsMC4wNi0xLjQyLDAuMTItMi4xLDAuMTJoLTEuNDhoLTAuOTNoLTIuNjVjLTUuNjgsMC0xNS4xMi0wLjE4LTE1LjgtMC44NgoJCWMtMC4xMi0wLjEyLTAuMTItMC45OS0wLjEyLTIuMjhjMC0zLjc2LDAuMzEtMTEuMTEsMC4zMS0xMi43N3YtNC43NWMwLTMuMjcsMC4xOS02LjM2LDAuMTktOS41di0xMC4wNmMwLTAuMzEsMS40OC0wLjQzLDMuMDktMC40MwoJCWMxLjQ4LDAsMy4wOSwwLjA2LDMuNywwLjA2QzI1My4wOCwxMDAuMjQsMjU5LjY5LDEwMC40OSwyNjQuNSwxMDAuNjF6Ii8+Cgk8cGF0aCBjbGFzcz0iZmFyZ2UiIGQ9Ik0yNzAuNjgsMTA0LjU2YzAtMi4wNCwwLjEyLTMuNzcsMC41NS0zLjk1YzAuNDMsMCwzLjIxLDIuMSwzLjcsMi40N2MzLjE1LDIuNTksNi4yMyw1LjMxLDkuMDEsOC4yMQoJCWMyLjEsMi4xNiw0LjQ0LDMuOTUsNi42LDYuMDVjMS43MywxLjY3LDMuNTgsMy4yMSw1LjE4LDQuOTRjMS40OCwxLjU0LDMuMDIsMy4wOSw0LjMyLDQuODhjMC4wNi0wLjMxLDAuMDYtMC42MiwwLjA2LTAuOTMKCQljMC4wNi0wLjc0LDAuMTktMS40OCwwLjI1LTIuMjhjMC4xOS0yLjIyLDEuMTEtMTguMDIsMS43My0xOC4wMmgxLjNjMS43OSwwLDQuMiwwLjEyLDQuMiwxLjM2YzAsMi44NC0wLjE5LDUuNzQtMC4yNSw4LjUyCgkJYzAsMC4yNSwwLDAuNDksMCwwLjc0Yy0wLjMxLDguMDItMC45MywxMy43LTEuMjMsMjEuNDJjLTAuMDYsMS4xMSwwLDIuNTMtMC4zNywzLjQ2Yy0wLjEyLDAuNDMtMC40OSwwLjYyLTAuODYsMC42MgoJCWMtMC4yNSwwLTAuNDktMC4wNi0wLjc0LTAuMjVjLTEuMjMtMC45OS0yLjUzLTIuMTYtMy41OC0zLjM5Yy0zLjQ2LTMuNzYtNy41My02Ljg1LTEwLjk5LTEwLjU1Yy0yLjEtMi4yMi00LjM4LTQuMzItNi40OC02LjU0CgkJYy0xLjExLTEuMTctMi4yOC0yLjIyLTMuMzMtMy4zOWMtMC4zMS0wLjMxLTAuNDktMC42OC0wLjgtMC45OWMtMC42Mi0wLjc0LTAuMTgtMC41Ni0wLjkzLTEuMTdjMC4xOSw0LjQ0LDAuMzcsOS4yNiwwLjM3LDE0LjA3CgkJYzAsMS43OS0wLjA2LDMuNTItMC4wNiw1LjMxYzAsMC42OCwwLjA2LDEuNiwwLjA2LDIuNTNjMCwxLjM2LDAsMi43OC0wLjA2LDMuNjRjMCwwLjI1LTAuMzEsMC4yNS0wLjY4LDAuMjUKCQljLTAuNjIsMC0xLjYtMC4xOC0yLjM0LTAuMThoLTAuMTljLTAuODYsMC4wNi0yLjEsMC4xOC0yLjksMC4xOGMtMC41NiwwLTAuOTktMC4wNi0wLjk5LTAuMjVsLTAuMTItMy4wOXYtMS4xMQoJCWMwLTIuMTYsMC4xMi00LjMyLDAuMTItNi40MmMwLTAuNjItMC4wNi0xLjIzLTAuMDYtMS43OWMtMC4xMi02LjMsMC0xMi40MS0wLjMxLTE4LjQ1QzI3MC44LDEwOS4zOCwyNzAuNjgsMTA2Ljc4LDI3MC42OCwxMDQuNTZ6IgoJCS8+Cgk8cGF0aCBjbGFzcz0iZmFyZ2UiIGQ9Ik0zMTUuODYsMTIwLjYxYy0yLjU5LTEuODUtNC44OC01LjMxLTQuODgtOS43NWMwLTYuMDUsMS45Ny04LjM5LDYuMjMtMTAuMThjMS40Mi0wLjYyLDMuMTUtMC44LDQuODgtMC44CgkJYzIuNzgsMCw1LjQ5LDAuNjIsNi45NywxLjU0YzAuOTMsMC42MiwxLjc5LDEuMTcsMi42NSwxLjg1YzAuOTMsMC43NCwzLjAyLDIuNDEsMy4wMiwyLjg0YzAsMS4xNy0yLjUzLDUuNzQtMy42NCw1Ljc0CgkJYy0wLjA2LDAtMC4xOSwwLTAuMjUtMC4wNmMtMC42Mi0wLjgtMi40MS0xLjkxLTMuMTUtMi41M2MtMS42LTEuMTEtMy43LTIuMzQtNS45OS0yLjM0Yy0wLjY4LDAtMS4zLDAuMDYtMS45NywwLjMxCgkJYy0wLjgsMC4zMS0xLjQ4LDAuOTMtMS42NywxLjg1djAuOGMwLDMuODksMy4wMiw1Ljc0LDUuNDMsNy4xNmMwLjk5LDAuNjIsNC4wNywzLjAyLDQuODEsMy45NWMyLjQxLDIuOTYsMy4zMywzLjc2LDQuNjksNy43OAoJCWMwLjM3LDAuOTksMC40OSwyLjEsMC40OSwzLjI3YzAsMC44LTAuMTIsMS42Ny0wLjI1LDIuNDdjLTAuNDMsMi44NC0yLjI4LDQuNzUtNC4wMSw2LjM2Yy0xLjc5LDEuMjMtMy44OSwxLjg1LTUuODYsMS44NQoJCWMtMy4xNSwwLTYuMTEtMS40Mi03Ljk2LTMuOTVjLTAuNjgtMC45My00LjA3LTUuOTItNC42My03LjQxYzAtMC40MywxLjA1LTEuMDUsMS43OS0xLjk3YzAuNDMtMC41NiwxLjg1LTEuNDgsMi42NS0xLjczCgkJYzAsMCwyLjg0LDMuOTUsMy40LDQuODhjMC42MiwwLjg2LDEuNDgsMS40OCwyLjQ3LDEuOTFjMC43NCwwLjI1LDEuNDgsMC40MywyLjE2LDAuNDNjMS42LDAsMi44NC0wLjg2LDIuODQtMi43MnYtMC4zN3YtMC4wNgoJCWwtMC4wNi0wLjA2QzMyNC45OSwxMjUuMjQsMzIwLjI0LDEyMy42OSwzMTUuODYsMTIwLjYxeiIvPgoJPHBhdGggY2xhc3M9ImZhcmdlIiBkPSJNMzUxLjc4LDEyMC40OGgwLjU1YzEuMTEsMCwxLjMsMC4wNiwxLjMsMC44NmMwLDAuMzEtMC4wNiwwLjY4LTAuMDYsMS4yM2MwLDAuNjgtMC4xMiwyLjktMC4xMiwzLjU4CgkJYzAsMC40OS0wLjY4LDAuNDktMS43MywwLjQ5aC0wLjY4Yy0zLjIxLDAtNi42NywwLjQ5LTEyLjIyLDAuNDloLTAuOTNjLTAuNjgsMC0wLjgtMC4xOC0wLjgtMS42YzAtMS4xMS0wLjMxLTMuNjQtMC4zMS00LjYzCgkJYzAtMC41NiwxLjY3LTAuNjgsMy4yNy0wLjY4YzAuOCwwLDEuNjcsMC4wNiwyLjIyLDAuMDZjNC44MSwwLDMuNzYsMC4wNiw2LjI5LDAuMThIMzUxLjc4eiIvPgo8L2c+CjxnPgoJPHBhdGggY2xhc3M9ImZhcmdlIiBkPSJNMTA5LjM0LDMzOS42OWwtMC44NC0wLjUyYy0yLjE0LTEuMzYtNC4yOC0yLjc5LTYuNDgtNC4xNWwtMC42NS0wLjM5Yy0xLjQzLTAuODQtMi44NS0xLjY4LTQuMTUtMi42NgoJCWwtMS4yMy0wLjg0Yy0xLjU2LTEuMS0yLjk4LTEuODEtNC40Ny0yLjk4Yy0wLjkxLTAuNjUtMy42My0yLjE0LTMuNjMtMi45MmMwLTAuNzEsMS43NS0xLjYyLDIuMDctMS45NGwwLjcxLTAuNzEKCQljMC4yNi0wLjI2LDAuNjUtMC41MiwwLjkxLTAuNzhsMC42NS0wLjQ1YzEuODItMS41NiwzLjgyLTIuOTIsNS43LTQuNDFjMS4zLTAuOTcsMi43Mi0xLjgxLDQuMTUtMi41OQoJCWMyLjA3LTEuMyw0LjA4LTIuNzksNi4yOS00LjAyYzAuNTgtMC4zMiwxLjQzLTAuOTcsMi4wMS0xLjFjMC43MS0wLjQ1LDEuMy0wLjY1LDEuNjItMC42NWMwLjUyLDAsMC41OCwwLjQ1LDAuNTgsMS4xbC0wLjEzLDYuMDMKCQljMCwxLjMtMC4xOSwxLjMtMC45NywxLjc1Yy0zLjY5LDIuMzMtOC43NSw0Ljg2LTEyLjEyLDcuNzhjMC4zOSwwLjI2LDEuNzUsMS4wNCwyLjE0LDEuM2MyLjE0LDEuMzYsNC4yMSwyLjg1LDYuNDgsNC4wOGwwLjc4LDAuNDUKCQljMC45NywwLjUyLDEuNDMsMC45NywyLjE0LDEuNDljMC43MSwwLjUyLDAuODQsMC4xOSwwLjk3LDEuNjhjMC4xMywxLjQ5LDAuNDUsNC4wOCwwLjQ1LDYuMDNjMCwwLjU4LDAsMC45MS0wLjE5LDAuOTEKCQlzLTAuNTItMC4yNi0xLjE3LTAuNjVDMTEwLjQ0LDM0MC4yMSwxMDkuODYsMzQwLjAyLDEwOS4zNCwzMzkuNjl6Ii8+Cgk8cGF0aCBjbGFzcz0iZmFyZ2UiIGQ9Ik0xMjEuNTksMzQ0LjQzaC0wLjQ1aC0wLjE5Yy0wLjc4LDAtMi4zMywwLjE5LTMuNjMsMC4xOWMtMS4wNCwwLTEuODgtMC4xMy0yLjAxLTAuNjUKCQljNi4xNi0xMS40MSwxMS40Ny0xOS42NCwxNi40Ny0zMC45MmMwLTAuMTMsNC45My0xMC43LDQuOTMtMTAuODNjMC4wNi0wLjM5LDAuMjYtMC41MiwwLjY1LTAuNTJzMS4xLDAuMTMsMi4wNywwLjEzCgkJYzAuMTksMCwwLjUyLDAsMC44NCwwYzEuMzYsMCwzLjU3LDAuMDYsMy41NywwLjQ1YzAsMS4zLTMuODIsOS43OS00LjE1LDEwLjMxbC0wLjA2LDAuMDZjLTAuMzksMC41OC0wLjU4LDEuMDQtMC45MSwxLjY4CgkJYy01LjE5LDEwLjYzLTEwLjE4LDE5Ljc3LTE1LjM2LDI5Ljg5QzEyMi4xNywzNDQuMjMsMTIyLjM2LDM0NC40MywxMjEuNTksMzQ0LjQzeiIvPgoJPHBhdGggY2xhc3M9ImZhcmdlIiBkPSJNMjgwLjIyLDM0MS4xOWMtMC4xOSwwLTAuMTktMC4zMi0wLjE5LTAuOTFjMC0xLjk1LDAuMzItNC41NCwwLjQ1LTYuMDNjMC4xMy0xLjQ5LDAuMjYtMS4xNywwLjk3LTEuNjgKCQljMC43MS0wLjUyLDEuMTctMC45NywyLjE0LTEuNDlsMC43OC0wLjQ1YzEuMzYtMC43OCwyLjY2LTEuNjIsNC4wMi0yLjRjMC44NC0wLjUyLDEuNjItMS4xNywyLjQ2LTEuNjkKCQljMC4zOS0wLjI2LDEuNzUtMS4wNCwyLjE0LTEuM2MtMy4zNy0yLjkyLTguNDMtNS40NC0xMi4xMi03Ljc4Yy0wLjc4LTAuNDUtMC45Ny0wLjQ1LTAuOTctMS43NWwtMC4xMy02LjAzCgkJYzAtMC42NSwwLjEzLTEuMSwwLjY1LTEuMWMwLjMyLDAsMC44NCwwLjE5LDEuNTYsMC42NWMwLjU4LDAuMTMsMS40MywwLjc4LDIuMDEsMS4xYzIuMiwxLjIzLDQuMjEsMi43Miw2LjI5LDQuMDIKCQljMS40MywwLjc4LDIuODUsMS42Miw0LjE1LDIuNTljMS44OCwxLjQ5LDMuOTUsMi44NSw1Ljc3LDQuNDFsMC41OCwwLjQ1YzAuMjYsMC4yNiwwLjY1LDAuNTIsMC45MSwwLjc4bDAuNzgsMC43MQoJCWMwLjMyLDAuMzIsMi4wMSwxLjIzLDIuMDEsMS45NGMwLDAuNzgtMi43MiwyLjI3LTMuNjMsMi45MmMtMS40OSwxLjE3LTIuODUsMS44OC00LjQxLDIuOThsLTEuMjMsMC44NAoJCWMtMS4zLDAuOTctMi43MiwxLjgxLTQuMTUsMi42NmwtMC43MSwwLjM5Yy0xLjIzLDAuNzEtMi40NiwxLjQzLTMuNjMsMi4yN2MtMC45MSwwLjY1LTEuODgsMS4zLTIuODUsMS44OGwtMC44NCwwLjUyCgkJYy0wLjUyLDAuMzMtMS4xLDAuNTItMS42MiwwLjg0QzI4MC43NCwzNDAuOTMsMjgwLjQyLDM0MS4xOSwyODAuMjIsMzQxLjE5eiIvPgoJPHBhdGggY2xhc3M9ImZhcmdlIiBkPSJNMzE5LjkxLDMzNC45NmgwLjY1YzEuMTcsMCwxLjMsMC4wNiwxLjMsMC45MXYxLjM2YzAsMC43MS0wLjE5LDIuMi0wLjE5LDIuOTJjMCwwLjUyLTAuNzEsMC41Mi0xLjgyLDAuNTIKCQloLTAuNzFjLTMuMzcsMC0xMS4yMiwwLjUyLTE3LjA1LDAuNTJoLTAuOTdjLTAuNzEsMC0wLjg0LTAuMi0wLjg0LTEuNjljMC0xLjE3LTAuMzItMi45OC0wLjMyLTQuMDJjMC0wLjU4LDEuNzUtMC43MSwzLjQ0LTAuNzEKCQloMi4yN2M1LjA2LDAsNC4wMiwwLjA2LDYuNjgsMC4xOUgzMTkuOTF6Ii8+CjwvZz4KPHBhdGggY2xhc3M9ImZhcmdlIiBkPSJNMzMwLjEzLDM5LjM3YzE0LjgsMTAuNDksMjguMzcsMjQuMDUsMzguMjMsMzkuNDZjMTcuODgsMjcuMTMsMzAuMjEsNTcuMzUsMzkuNDYsOTMuMTEKCWM1LjU1LDIzLjQzLDYuMTYsNDkuOTQsNi4xNiw3OC4zMWMwLDQxLjkzLTE3Ljg4LDY5LjA2LTM1Ljc2LDkzLjcyYy0xMS4xLDE0LjgtMjQuNjYsMjUuODktMzkuNDYsMzcKCWMtNi4xNiw0LjMyLTEyLjk1LDguMDEtMTkuNzMsMTEuMWMtMzAuODMsMTMuNTYtNTkuMTksMjYuNTEtMTAxLjEyLDI3LjEzYy0zLjA4LDAtNS41NSwwLTguNjMsMAoJYy0xMTEuNjEsMC0xNjUuMjUtNTMuMDItMTkyLjM4LTEzNy41Yy02LjE3LTIwLjM1LTYuMTctNDguNzEtNi4xNy03My45OXYtMy43QzEwLjc0LDk5LjE5LDEwMy44NSwzLDIxOS43NiwzCgljMjIuODEsMCw0Ni4yNCwzLjcsNzAuMjksMTEuNzFDMzA0Ljg1LDIxLjQ5LDMxNy44LDMwLjEzLDMzMC4xMywzOS4zN3ogTTM0LjE3LDI3OGMxMS43MiwzNi4zOCwzMC4yMiw2NS4zNiw1Ni4xMSw4OC4xNwoJYzMwLjgzLDIyLjgxLDY0LjEzLDM2LjM4LDExNC4wNywzNi4zOGMxMS4xLDAsMjMuNDMtMC42MiwzNi45OS0xLjg1YzIyLjgyLTQuMzIsNDQuMzktMTMuNTYsNjQuNzUtMjEuNTgKCWMyMC45Ni05LjI1LDQwLjA4LTIyLjIsNTMuNjQtMzguODVjMTcuODgtMjQuMDQsMzctNDkuMzMsMzctOTEuMjVjMC00LjMyLDAtOC4wMiwwLTEyLjM0YzAtMTQuMTgsMC0yOC4zNi0xLjg1LTQxLjkyCgljLTYuMTctNDAuMDgtMjAuOTctNzEuNTMtMzcuNjEtMTAxLjEzYy0xMS43Mi0yMi4xOS0zMS40NS0zNi45OS01MS44LTUwLjU2Yy0xMi4zMy04LjAxLTI1Ljg5LTE1LjQxLTQyLjU0LTE4LjUKCWMtMTQuMTgtMS44NS0yNy43NS0zLjA4LTQwLjY5LTMuMDhjLTYwLjQzLDAtMTE0LjA3LDIxLjU4LTE0OS44Myw2Ny44M0M0OC4zNSwxMjAuMTUsMjgsMTYyLjA4LDI4LDIxNC40OXYwLjYyCglDMjgsMjM3LjkyLDI4LjYyLDI2MC43MywzNC4xNywyNzh6Ii8+Cjwvc3ZnPgo=');
		
				// Montering
				eksIkonLink.appendChild(eksIkonCont);
				eksIkonLink.appendChild(eksIkonLabel);
				eksIkon.appendChild(eksIkonLink);
				// Legg ut i sida
				let m = document.getElementById('menu');
				if (m != null){
					m.appendChild(eksIkon);
				}

}
})();