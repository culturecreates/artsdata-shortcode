class ArtsdataOrgDetail extends HTMLElement {

  set org(org) {
    this.innerHTML = `<div>
    <h3> ${  org.namePref || org.nameEn  || org.nameFr }</h3>
    <p> ${ org.address.addressLocality  }, ${ org.address.addressRegion  }, ${ org.address.addressCountry  } </p>
    <p> <a href="${ this.officialUrl(org.url[0])}">${this.officialUrl(org.url[0])}</a> </p>
    <br>
    <p> Organization Type: ${this.organizationType(org.additionalType) } </p>
    <p> Presenter Type: Multidisciplinary </p>
    <p> Disciplines: ${this.disciplines(org.additionalType) } </p>
    <p> Presentation Format: ${this.presentationFormat(org.additionalType) } </p>
    <br>
    <p> Artsdata ID:  <a href='${ org.id}'> ${ org.id.split('/resource/')[1]}</a>
    <p> Canadian Business Number: ${org.businessNumber} </p>
    <br>
     ${this.linkExtraction(org.sameAs, "facebook.com", "Facebook")} 
     ${this.linkExtraction(org.sameAs, "twitter.com", "Twitter")}
     ${this.linkExtraction(org.sameAs, "youtube.com", "Youtube")} 
     ${this.linkExtraction(org.sameAs, "wikipedia.org", "Wikipedia")}
     ${this.linkExtraction(org.sameAs, "instagram.com", "Instagram")}
     <br>
     Links: ${this.links(org.sameAs)} 
    <p> Venues: <br> ${this.venues(org.location)}  </b></p>
    <code> ${ JSON.stringify(org)}</code>
    </div>`
  }

  linkExtraction(sameAs, detectionStr, label) {
    let extractId = ''
    if (typeof sameAs == 'object') {
     
      let id = ''
      if (sameAs.length) {
        sameAs.forEach(data => {
          if (typeof data == 'object') {
            id = data.id
          } else {
            id = data
          }
          if (id.includes(detectionStr)) {
            extractId = id
          }
        })
      } else {
        if (sameAs.id.includes(detectionStr)) {
          extractId = sameAs.id
        }
      }
    } else {
      if (sameAs.includes(detectionStr)) {
        extractId = sameAs
      }
    }
    if (extractId.length) {
      extractId = "<a href=\"" + extractId + "\">" + label + "\</a\>"
    }
    return extractId
  }


  organizationType(additionalType) {
    return this.generalType(additionalType, "PrimaryActivity")
  }

  presenterType(additionalType) {
    return this.generalType(additionalType, "PrimaryActivity")
  }

  presentationFormat(additionalType) {
    return this.generalType(additionalType, "PresentingFormat")
  }

  disciplines(additionalType) {
    return this.generalType(additionalType, "Discipline")
  }
  
  generalType(allTypes, detectionStr) {
    let str = ""
    allTypes.forEach(data => {
      if (data.id) {
        if (data.id.includes(detectionStr)) {
          if (data.label) {
            str += data.label + ", "
          }
        }
      }
    })
    return str.slice(0, -2)
  }

  links(sameAs) {
    let html_list = "<ul>"
    if (sameAs.length) {
      sameAs.forEach(data => {
        if (data.id) {
          html_list += "<li>" + data.id
        } else {
          html_list += "<li>" + data
        }
      })
      html_list += "</ul>"
    } else {
      html_list += "<li>" + sameAs
    }
    return html_list
  }

  venues(locations) {
    let html_list = "<ul>"
    if (locations.length) {
      locations.forEach(data => {
        if (data.roleName) {
          html_list += "<li>" + data.roleName
          html_list += " : <b>" + data.location.namePref + "</b>"
        }
      })
      html_list += "</ul>"
    } else {
      html_list += "<li>" + locations
    }
    return html_list
  }


  main_attributes(types) {
    let html_list = "<ul>"
    types.forEach(data => {
      if (data.label) {
        html_list += "<li>" + data.label
      }
    })
    html_list += "</ul>"
    return html_list
  }

  officialUrl(url) {
    if (typeof url != 'object') {
      return url
    }
    return ''
  }

  attendanceMode(mode) {
    var modeHtml = ""
    if (mode == "MixedEventAttendanceMode") {
      modeHtml = "AUSSI EN SPECTACLE VIRTUEL"
    } else if (mode == "OnlineEventAttendanceMode") {
      modeHtml = "SPECTACLE VIRTUEL"
    }
    return `<span style='color: red;'>${modeHtml}</span>`
  }

  dateFormat(event) {
    return new Date(event.startDate).toLocaleString('fr-FR', {
      dateStyle: 'long',
      timeStyle: 'short',
      hour12: false,
      timeZone: 'EST'
    }) || event.startDateWithoutTime
  }
}


customElements.define('artsdata-org-detail', ArtsdataOrgDetail)