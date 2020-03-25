
function isIE()
{
	if (document.all) return true;
	return false;
}

function getElement( id )
{
	return document.getElementById( id );
}

function getTableRow( tableID, row )
{
	return document.getElementById( tableID ).rows.item( row );
}

function getTableCell( tableID, column, row )
{
	return getTableRow( tableID, row ).cells.item( column );
}

function getTableCellText( tableID, column, row )
{
	return getTableCell( tableID, column, row ).innerHTML;
}

function setTableCellText( tableID, column, row, text )
{
	getTableCell( tableID, column, row ).innerHTML = text;
}

function getSelectedOption( selectControl )
{
	for (var i=0; i<selectControl.options.length;i++)
		if (selectControl.options[i].selected)
			return selectControl.options[i]
}

function setSelectedOption( selectControl, optionValue )
{
	for (var i=0; i<selectControl.options.length;i++)
		if (selectControl.options[i].value == optionValue)
		{
			selectControl.options[i].selected = true
			break
		}
}

function setSelectedOptionText( selectControl, optionText )
{
	for (var i=0; i<selectControl.options.length;i++)
		if (selectControl.options[i].text == optionText)
		{
			selectControl.options[i].selected = true
			break
		}
}

function getSelectedOptionText( selectControl )
{
	for (var i=0; i<selectControl.options.length;i++)
		if (selectControl.options[i].selected)
			return selectControl.options[i].text
}

function formChange()
{
	formChange2( document.form1 );
}

function formChange2( form )
{
	if (form) for (i=0; i<form.elements.length; i++)
	{  
		e = form.elements[i];
		if (e.type == "submit") e.disabled = 0;
		if (e.type == "reset") e.disabled = 0;
		if (e.type == "button") e.disabled = 0;
	}
}

// ids = array of input ids for required inputs
function formChange3( ids )
{
	formChange();

	disable = false;

	for (var i in ids)
	{
		if (!getElement( ids[i] ).value) disable = true;
	}

	if (disable)
	{
		for (i=0; i<document.form1.elements.length; i++)
		{
			e = document.form1.elements[i];
			if (e.type == "submit") e.disabled = 1;
			if (e.type == "reset") e.disabled = 1;
		}
	}
}

function clickReset()
{
	with (document.form1)
	{
		for (i=0; i<elements.length; i++)
		{  
			e = elements[i];
			if (e.type == "submit") e.disabled = 1;
			if (e.type == "reset") e.disabled = 1;
			if (e.type == "button") e.disabled = 1;
		}
	}
}

function cbSelectAll( form )
{
	for (i=0; i<form.elements.length; i++)
	{
		if (form.elements[i].type == "checkbox") form.elements[i].checked = true;
	}
}

function cbSelectNone( form )
{
	for (i=0; i<form.elements.length; i++)
	{
		if (form.elements[i].type == "checkbox") form.elements[i].checked = false;
	}
}

function showPopupMessage( e, description, width )
{
	popup = getElement( "popDiv" );
	popup.style.visibility = "visible";
	popup.firstChild.data = description;
	if (isIE())
	{
		popup.style.left = document.body.scrollLeft + e.clientX + 10;
		popup.style.top = document.body.scrollTop + e.clientY - 30;
	}
	else
	{
		popup.style.left = pageXOffset + e.clientX + 10;
		popup.style.top = pageYOffset + e.clientY - 30;
	}

	if (width)
		popup.style.width = width;
	else
		popup.style.width = 300;
}

function showPopupMessageArray( e, lines )
{
	popup = getElement( "popDivArray" );
	popup.style.visibility = "visible";

	for (i=0; i<lines.length; i++)
	{
		popup.appendChild( document.createTextNode( lines[i] ) );
		popup.appendChild( document.createElement( 'br' ) );
	}

	if (isIE())
	{
		popup.style.left = document.body.scrollLeft + e.clientX + 10;
		popup.style.top = document.body.scrollTop + e.clientY - lines.length * 10;
	}
	else
	{
		popup.style.left = pageXOffset + e.clientX + 10;
		popup.style.top = pageYOffset + e.clientY - lines.length * 10;
	}
	popup.style.width = 300;
}

function hidePopupMessage( e )
{
	popup = getElement( "popDiv" );
	popup.style.visibility = "hidden";
}

function hidePopupMessageArray( e )
{
	popup = getElement( "popDivArray" );
	popup.style.visibility = "hidden";

	while (popup.hasChildNodes())
	{
		popup.removeChild( popup.lastChild );
	}
}

function onLoad()
{
	tmpImage = new Image;
	//tmpImage.src = "/img/gdtab/tab_black.png";
	tmpImage.src = "/Kollabra/gif/tabs/tab_light.png";
	//tmpImage.src = "/img/gdtab/tab_dark.png";
}

function makeFileUploadWindow( destDir )
{
	window.open(
		"fileUpload/fileUploadMain?destDir=" + destDir,
		"fileUploadMain",
		"menubar=no,resizable=yes,width=600,height=600"
	);
}

function makeProgressWindow()
{
	window.open(
		"fileUpload/fileUploadProgress2?refresh=1",
		"Kollabra",
		"menubar=no,resizable=yes,width=600,height=200"
	);
}





