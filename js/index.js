import { artsdataApiIndex } from "./artsdataApi.js"
import { artsdataApiDetail } from "./artsdataApi.js"
import {artsdataApiEvents } from "./artsdataApi.js"
import "./artsdata-orgs.js"
import "./artsdata-org-detail.js"
import "./artsdata-event.js"

window.addEventListener('load',() => {
  if ( getQueryString().uri ) {
    orgDetail(getQueryString().uri)
  }
  else {
    orgs()
  }
});

async function orgs() {
  try {
    displayLoading()
    const res = await fetch(artsdataApiIndex)
    const json = await res.json()
    const main = document.querySelector('main')
    hideLoading()
   
    json.data.sort((a,b) => (a.namePref > b.namePref) ? 1 : ((b.namePref > a.namePref) ? -1 : 0)).forEach(org => {
      const el = document.createElement('artsdata-orgs')
      el.org = org
      main.appendChild(el)
    })
  } catch (e) {
    console.log(e)
    hideLoading()
    displayError()
  }
}

async function orgDetail(uri) {
  // try {
    displayLoading()
    const main = document.querySelector('main')

    const res = await fetch(artsdataApiDetail + '&uri=' + uri)
    const json = await res.json()
    hideLoading()
    json.data.forEach(org => {
      const el = document.createElement('artsdata-org-detail')
      el.org = org
      main.appendChild(el)
    })
    const mosaic = document.querySelector('#mosaic')
    const resEvents = await fetch(artsdataApiEvents + '&predicate=schema:organizer&object=' + uri)
    const jsonEvents = await resEvents.json()  
    const eventHeading = document.createElement('h1') 
    if (jsonEvents.data.length > 0) {
      eventHeading.innerHTML = "Upcoming Events"
    } else {
      eventHeading.innerHTML = "No upcoming events found"
    }  
    main.appendChild(eventHeading)
    jsonEvents.data.forEach(event => {
      const elEvent = document.createElement('artsdata-event')
      elEvent.event = event
      mosaic.appendChild(elEvent)
    })

  // } catch (e) {
  //   console.log(e)
  //   displayError()
  // }
}
  

function displayLoading() {
  const e = document.createElement('div')
  e.innerHTML = 'Loading data from Artsdata.ca...'
  document.querySelector('main').appendChild(e)
}

function hideLoading() {
  document.querySelector('main').children[0].remove()
}

function displayError() {
  const e = document.createElement('div')
  e.innerHTML = 'Error: Artsdata.ca is not reachable'
  document.querySelector('main').appendChild(e)
}

function getQueryString() {
  var queryStringKeyValue = window.parent.location.search.replace('?', '').split('&');
  var qsJsonObject = {};
  if (queryStringKeyValue != '') {
      for (let i = 0; i < queryStringKeyValue.length; i++) {
          qsJsonObject[queryStringKeyValue[i].split('=')[0]] = queryStringKeyValue[i].split('=')[1];
      }
  }
  return qsJsonObject;
}


