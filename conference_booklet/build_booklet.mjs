import { writeFileSync } from "node:fs";
import { resolve } from "node:path";

const out = resolve("conference_booklet/ICA_Regional_Hub_Thailand_2026_Booklet.html");

const assets = {
  hub: "../im/ICA26_regional_hub_-_bangkok_logo.webp",
  ica: "../im/LogoICA.webp",
  chula: "../im/123.png",
  faculty: "../im/LOGO_Eng.webp",
  thaiMediaFund: "../im/Aw_Thai Media Fund_Logo2021-05.webp",
  anpor: "../im/1008453.webp",
  nida: "../im/GSCM.webp",
  jc: "../im/New-JC-Logo-2018-Update-Cleanup_Outline-01.webp",
  damrong: "../im/S__13172760_0.webp",
  swu: "../im/S__13172761_0.webp",
  cmct: "../im/logooo-1.webp",
  psu: "../im/psu-masscomm.webp",
};

const status = "As of 1 June 2026";

const paper = (id, title, authors, affiliation, flags = []) => ({
  id,
  title,
  authors,
  affiliation,
  flags,
});

const special = (html) => ({ special: true, html });

const sessions = {
  A: {
    label: "Parallel Session A",
    time: "11:00-12:30",
    rooms: [
      {
        room: "Plenary Hall / ROOM A (Mandarin A)",
        title:
          "A1 | Narrative Inequality in Digital Battlegrounds: Social Media, Influencers, and Public Perception of the Thai-Cambodian Conflict",
        special: special(`
          <p class="eyebrow">Thai Media Fund Special Session · All Onsite</p>
          <p>This panel explores an example of communication inequality, where a small group of influencers and news agencies can dominate public discourse in the Thai-Cambodian Conflict, often prioritizing nationalistic views over factual and balanced information.</p>
          <p>The panel highlights an urgent need to foster media literacy and encourage all stakeholders, including influencers, media outlets, and social platforms, to exercise social responsibility rather than chasing engagement through conflict.</p>
          <p><strong>Speakers</strong><br>Dr. Dhanakorn Srisooksai, CEO, Thai Media Fund<br>Dr. Chamnan Ngammaneeudom, Deputy CEO, Thai Media Fund<br>Nattapol Zupasit, Thai Media Fund<br>Thamrong Chittapasata, Thai Media Fund</p>
          <p><strong>Discussant</strong><br>Asst. Prof. Chanansara Oranop (Ph.D), Assistant Dean, Faculty of Communication Arts, Chulalongkorn University</p>
        `),
      },
      {
        room: "ROOM B (Budsaba)",
        title:
          "A2 | The Editorial Algorithm: AI, Automation, and the Accountability Gap in Asian Media",
        papers: [
          paper(
            "#129",
            "Automating the News: AI Anchors, Hype, and Bounded Journalism in China",
            "Chen, Yiming",
            "Xi'an Jiaotong-Liverpool University, China"
          ),
          paper(
            "#167",
            "Governing AI in Journalism: Media Company and News Rooms' (Self) Regulation in Using AI for News Production",
            "Manalu, S. Rouli",
            "Universitas Diponegoro, Indonesia"
          ),
          paper(
            "#172",
            "Creative AI Ecosystem for Children and Youth: A Comparative Analysis of Global and Regional Policy Frameworks",
            "Devahastin, Dean Deeprom",
            "KidWise Studios, Thailand"
          ),
          paper(
            "#189",
            "Innovation as Aspirational Performance: Big Data Development in Guizhou, China",
            "Zhao, Hanqing",
            "Keio University, Japan"
          ),
          paper(
            "#183",
            "Operationalizing Human-in-the-Loop Governance Across the AI Application Development Lifecycle: The L-HAT Framework",
            "Suksakul, Parm",
            "Chulalongkorn University, Thailand"
          ),
        ],
      },
      {
        room: "ROOM C (Rodsukon)",
        title:
          "A3 | Between Evidence and Algorithm: Health Communication, Medical Authority, and the Platform Challenge",
        papers: [
          paper(
            "#108",
            "Efficacy of Constructive Journalism on Affective Wellbeing: A Systematic Review and Meta-Analysis",
            "Fu, Zhengke",
            "NingboTECH University, China"
          ),
          paper(
            "#135",
            "IDEA Model-Based Approaches to Risk and Crisis Communication in Non-Communicable Diseases",
            "Suppiah, S. Maartandan",
            "Universiti Utara Malaysia, Malaysia"
          ),
          paper(
            "#190",
            "Health Misinformation on Social Media: An Analysis of TikTok Content Related to Non-Communicable Diseases in the Philippines and Thailand",
            "Calib, Aleyah Nadia I.",
            "University of the Thai Chamber of Commerce, Thailand"
          ),
          paper(
            "#110",
            "The Meaning of Nursing Homes: A Study on Communication and Interpretation Among Thai Consumers of Different Generations",
            "Palapreewan, Phittaya",
            "Panyapiwat Institute of Management, Thailand"
          ),
          paper(
            "#137",
            "Crisis Communication and Public Health Messaging Framework Analysis for Hazmat Incident and Occupational Chemical Leakage in Thailand",
            "Rakshit, Pornpidcha",
            "Faculty of Medicine Ramathibodi Hospital, Mahidol University, Thailand"
          ),
        ],
      },
      {
        room: "ROOM D (Karaked)",
        title:
          "A4 | The Price of Visibility: Queer Identities, Gendered Labour, and the Surveillance of Digital Selves",
        papers: [
          paper(
            "#116",
            "Platform-Mediated Thai Queerness: Thai BL, Global Streaming, and Messy Hybrid Masculinities",
            "dela Cruz, Erwin James Alonzo",
            "Thammasat University, Thailand"
          ),
          paper(
            "#106",
            "Reclaiming the Tourist Gaze: Indigenous Self-Representation Among Peruvian Content Creators and Its Resonance in Southeast Asian Digital Contexts",
            "Laura Paima, Emily Cecilia",
            "Universitas Gadjah Mada, Indonesia"
          ),
          paper(
            "#121",
            "Controlling Visibility: Trans Women, Identity, and Risk on Instagram",
            "Putri, Sukma",
            "Monash University, Australia"
          ),
          paper(
            "#155",
            "From Taboo to Meme: Negotiating Queer Meanings through 'Jomok' Content on Indonesian TikTok",
            "Firdaus, Muhammad Nauris",
            "The University of Melbourne, Australia"
          ),
          paper(
            "#213",
            "Gendered Motivations and Platformed Sexual Labor: Cis and Trans Women Creators on OnlyFans",
            "Yutthaworakool, Saittawut",
            "Asian Institute of Technology, Thailand"
          ),
        ],
      },
    ],
  },
  B: {
    label: "Parallel Session B",
    time: "15:30-17:00",
    rooms: [
      {
        room: "Plenary Hall / ROOM A (Mandarin A)",
        title: "B1 | Special Talk on Creative Economy and Journalism in China",
        special: special(`
          <p class="eyebrow">Special Talk Session I</p>
          <p><strong>Creative and Cultural Industries in Asia</strong></p>
          <p>This session discusses the transformation of creative industries in Asia, focusing on cultural labour, platform economies, and digital media industries.</p>
          <ol>
            <li><strong>Creative Labour and Trade Unions in East Asia</strong><br>Jocelyn Yi-Hsuan Lai, Associate Professor, Department of Communication Arts and Chief of Internationalization, College of Communication, Fu Jen Catholic University</li>
            <li><strong>Digital Platform and Transformation of Creative Work in Malaysia</strong><br>Kenneth Lee Tze Wui, Media Scholar and Social Anthropologist; Assistant Professor and Head, Department of Mass Communication, Faculty of Creative Industries, Universiti Tunku Abdul Rahman</li>
          </ol>
          <p><strong>Discussant</strong><br>Assoc. Prof. Alongkorn Parivudhiphongs (PhD), Deputy Dean, Faculty of Communication Arts, Chulalongkorn University</p>
          <p class="eyebrow">Special Talk Session II</p>
          <p><strong>Journalism Education in China</strong></p>
          <p>This session explores emerging trends in journalism education in China amid digital transformation and changing media industries.</p>
          <ol>
            <li><strong>Global Business Journalism Education in Contemporary China</strong><br>Lee Miller, Senior Editor, Bloomberg News and Visiting Professor of Journalism, Tsinghua University</li>
            <li><strong>Transforming Journalism Education in Contemporary China</strong><br>Zhang Jianzhong, Professor, School of Journalism and Communication, Guangxi University</li>
          </ol>
          <p><strong>Discussant</strong><br>Prof. Dr. Masato Kajimoto, Professor of Practice in Journalism, University of Hong Kong</p>
        `),
      },
      {
        room: "ROOM B (Budsaba)",
        title:
          "B2 | What Cables Carry: Communication Infrastructure, State Power, and the Right to Speak in Asia",
        papers: [
          paper(
            "#147",
            "The Impacts of China's SEA-H2X Submarine Cable: Telecommunications Regulatory Study under the NBTC Framework",
            "Kheokao, Thasan",
            "National Broadcasting and Telecommunications Commission (NBTC), Thailand"
          ),
          paper(
            "#202",
            "The 'ThAI' Nationalism: The Pandemic of Information Operation (IO) in Arousing Nationalistic Sentiment during the Age of AI",
            "Bintorleb, Asia",
            "National University of Singapore, Singapore"
          ),
          paper(
            "#157",
            "The Sonic Sovereignty of the Voiceless: Silence, Inequality, and Recognition in Southeast Asian Cinema",
            "Serisamran, Teerapong",
            "Chulalongkorn University, Thailand"
          ),
          paper(
            "#209",
            "The Marketing Communication Governance in the Digital Era: An Analysis of Substantive Provisions and Practical Gaps",
            "Panichpapiboon, Sopark",
            "University of the Thai Chamber of Commerce, Thailand"
          ),
          paper(
            "#169",
            "National Self-Reliance in Discursive Legitimation: A Study of Viet Nam's 'New Era' Discourse in State Media",
            "Gia Huy, Luu and Ngoc Thuy Duong, Le",
            "Diplomatic Academy of Vietnam, Vietnam (Q&A with co-author at the end) - Zoom"
          ),
          paper(
            "#131",
            "Decolonizing Experimental Methods in Communication Research: Notes from a Filipino Perspective",
            "Villacastin, Juven Nino",
            "University of Hawaii at Manoa, Philippines"
          ),
        ],
      },
      {
        room: "ROOM C (Rodsukon)",
        title:
          "B3 | The Truth Was Never Trending: News Avoidance, Disinformation, and the Democratic Information Crisis",
        papers: [
          paper(
            "#179",
            "A State of Knowledge Survey on Fact-Checking Research in Communication Studies",
            "Phothihang, Pratya",
            "Pibulsongkram Rajabhat University, Thailand"
          ),
          paper(
            "#133",
            "Selective News Avoidance and Misinformation Concerns among Women Councillors in Delhi, India",
            "Sharma, Annapurna",
            "Central University of Punjab, India"
          ),
          paper(
            "#174",
            "Media Coverage of Crime and Its Effects on Thai Audiences' Perceptions of Criminal Suspects and Persons of Interest",
            "Phansab, Chanamon",
            "Chulalongkorn University, Thailand"
          ),
          paper(
            "#136",
            "Triggering Polarization: Examining the Link Between Hard Talk Programs and Online Vigilantism in the Thai-Cambodian Border Dispute",
            "Oranop, Chanansara",
            "Chulalongkorn University, Thailand"
          ),
          paper(
            "#117",
            "Institutional Failure and Crisis of Communicative Action in Indonesia's August 2025 Demonstration",
            "Ardiyanto, Erik",
            "Universitas Paramadina, Indonesia"
          ),
          paper(
            "#203",
            "From Public Broadcasting to Platform Dependency: Regulatory Challenges, Information Inequality, and Media Access in Thailand",
            "Kamplean, Artima",
            "Faculty of Journalism and Mass Communication, Thammasat University, Thailand"
          ),
        ],
      },
      {
        room: "ROOM D (Karaked)",
        title:
          "B4 | When Everyone Is a Publisher: Platform Governance, Content Labour, and Commercial Communication in Asia's New Media Economy",
        papers: [
          paper(
            "#188",
            "Branding the Misbranded: An Exploratory Study of Influencer-Mediated Cultural Misbranding in Hanoi",
            "Nguyen, Chung Anh",
            "Vietnam Japan University - VNU, Vietnam"
          ),
          paper(
            "#218",
            "From Columnists to Influencers: A Historical Study on the Digital Transition of Thai Football Journalists",
            "Supakitcharoen, Apisit",
            "Chulalongkorn University, Thailand"
          ),
          paper(
            "#153",
            "The Personal Branding Process of Historical Knowledge Influencers: A Case Study of Dr. Wit Sittivaekin and Nat Klinmalee (Farose)",
            "Immonen, Max",
            "Srinakharinwirot University, Thailand"
          ),
          paper(
            "#201",
            "A Survey of the State of Research on Online Micro-Dramas in Mass Communication",
            "Kattirat, Witavas",
            "ChiangRai Rajabhat University, Thailand"
          ),
          paper(
            "#156",
            "Motivations of Porn Game Developers: Movement under Moral Boundaries Among Japanese Anime-Style Adult Game Developers on Steam",
            "Srigom, Warapob",
            "Mahidol University, Thailand"
          ),
        ],
      },
    ],
  },
  C: {
    label: "Parallel Session C",
    time: "09:00-10:30",
    rooms: [
      {
        room: "Plenary Hall / ROOM A (Mandarin A)",
        title:
          "C1 | The Assemblages of Agency: Negotiating Visibility in Asian Digital Spaces",
        special: special(`
          <p class="eyebrow">QUT Special Session · All Virtual</p>
          <p>This panel examines the complexities of digital visibility in Asian digital spaces. Through diverse case studies spanning digital advocacy in Indonesia, platform governance in Australia, virtual influencers in Vietnam, and cross-border creators in Myanmar, the speakers explore how marginalized groups negotiate agency amidst structural, cultural, and political constraints.</p>
          <p><strong>Speakers</strong></p>
          <ol>
            <li><strong>Dr. Alia Azmi</strong>, University of Bengkulu, Indonesia: online conversations on sexual violence and digital advocacy as a trajectory of agency.</li>
            <li><strong>Chuying Lu</strong>, University of Queensland, Australia: interaction architectures of Twitter/X during the COVID-19 pandemic and reply-based silencing.</li>
            <li><strong>Do Doan Hanh Nguyen</strong>, Queensland University of Technology, Australia: virtual influencers in Vietnam, representation, memefication, and commercialisation.</li>
            <li><strong>Dr. Yuxin Liu and Dr. Ming Zhang</strong>, Shanghai University of Political Science and Law, China; Wenzhou University of Technology, China: cross-border and transnational creators from Myanmar.</li>
          </ol>
          <p><strong>Discussant</strong><br>Dr. Xiaoting Yu, Queensland University of Technology</p>
        `),
      },
      {
        room: "ROOM B (Budsaba)",
        title:
          "C2 | Authority in Troubled Waters: Communication, Risk, and the Power to Shape Public Understanding (Hybrid)",
        papers: [
          paper(
            "#109",
            "Benevolent Leadership as a Relational Communication Signal: A Social Exchange Perspective on Customer Service Behavior",
            "Phochadom, Supissara",
            "Prince of Songkla University, Thailand"
          ),
          paper(
            "#158",
            "Mapping Policy Discourse on Flooding in Aceh: A Discourse Network Analysis of Government Responses",
            "Subektie, Rosalina",
            "Diponegoro University, Indonesia"
          ),
          paper(
            "#216",
            "News Framing of PM 2.5 Air Pollution in Thailand: A Comparative Study of National and Local News Websites",
            "Jitkaroon, Lalita",
            "Naresuan University, Thailand"
          ),
          paper(
            "#111",
            "Bridging the Implementation Gap in One Health: A Systematic Review of the Poultry Sector in Japan and Beyond",
            "Harmawan, Febriangga",
            "Ehime University, Japan",
            ["Virtual"]
          ),
          paper(
            "#193",
            "Constructing the 'Good Student': Digital Wellbeing and AI Use in Higher Education Discourses",
            "Buragohain, Dipima",
            "Chulalongkorn University, Thailand",
            ["Virtual"]
          ),
          paper(
            "#180",
            "Who Defines the Risk? Framing Analysis of Thailand's PM 2.5 Crisis",
            "Chanthapan, Worapron",
            "California State University, Long Beach, USA",
            ["Virtual"]
          ),
        ],
      },
      {
        room: "ROOM C (Rodsukon)",
        title:
          "C3 | Knowledge Is Not Enough: Health Communication, Behaviour Change, and the Body in Asian Contexts",
        papers: [
          paper(
            "#161",
            "Fighting a Laboring Women's Disease: Health Discourse about Uterine Prolapse in Socialist China (1958-1966)",
            "Zhang, Kaixuan",
            "NingboTech University, China"
          ),
          paper(
            "#115",
            "A DEMATEL-Based Systems Analysis of Health and Safety Risk Drivers Among Marginalized Worker Populations",
            "Bharadwaj, Manish",
            "ABV-IIITM Gwalior, India"
          ),
          paper(
            "#134",
            "Bridging the Knowledge-Behavior Gap: A Health Communication Study of Liver Fluke Prevention in Thailand",
            "Shaw, Kanyika",
            "Panyapiwat Institute of Management, Thailand"
          ),
          paper(
            "#149",
            "Health Communication through Edutainment: A Case Study of 'Raw Pork, Deafness, Do You Know?' by Tai Baan x Department of Disease Control",
            "Werajong, Oubonpun",
            "Department of Disease Control, Thailand"
          ),
          paper(
            "#168",
            "The Persuasive Impact of Narrative Storytelling in Entertainment-Education Audiovisual Media: Effects on Attitudes and Behavioral Intentions of Caregivers",
            "Sukittanon, Siwaporn",
            "Chiang Mai University, Thailand"
          ),
        ],
      },
      {
        room: "ROOM D (Karaked)",
        title:
          "C4 | Whose Story Is This? Historical Memory, Marginality, and the Right to Narrative Authority",
        papers: [
          paper(
            "#139",
            "Culturally Responsive Leadership Communication in Southeast Asia: A Case Study of Tony Fernandes and AirAsia",
            "Balakrishnan, Thiviya",
            "Tee Talks Services, Malaysia"
          ),
          paper(
            "#140",
            "Representation of Historical Events in Malaysian National Historical Film",
            "Abd Halim, Siti Nur Izra Safra",
            "Universiti Kebangsaan Malaysia, Malaysia"
          ),
          paper(
            "#173",
            "'Talking about my Beneficiaries': Knowledge Brokering Organisation Disrupting the Dominant Authority under Hierarchical Governance in the Global South",
            "Tan, Amanda",
            "Monash University Indonesia, Indonesia"
          ),
          paper(
            "#197",
            "Active Ageing through Meaning, Routine, and Voice: An Onsite Study of Older Adults in Myanmar",
            "Pyae, Aung",
            "Chulalongkorn University, Thailand"
          ),
          paper(
            "#152",
            "From Audience to Local Content Creators: Participatory Storytelling and the Expansion of Cultural Voices",
            "Srisaracam, Sakulsri",
            "Chulalongkorn University, Thailand"
          ),
        ],
      },
    ],
  },
  D: {
    label: "Parallel Session D",
    time: "15:30-17:00",
    rooms: [
      {
        room: "Plenary Hall / ROOM A (Mandarin A)",
        title:
          "D1 | The Global Algorithm: Digital Divides, Disinformation, and Algorithmic Power across Six Countries",
        papers: [
          paper(
            "#130",
            "The Reproduction of the Vertical Digital Divide in Vietnam's AI Education Strategy",
            "Tran, Long Xuan Bao",
            "Dalat University, Vietnam",
            ["Virtual"]
          ),
          paper(
            "#204",
            "The 'Second Parliament' in the Age of Algorithms: Invisibilisation and Transformation of French Street Politics",
            "Zuo, Chen",
            "Communication University of China, China",
            ["Virtual"]
          ),
          paper(
            "#181",
            "American Public and Commercial Medical Influence in Indonesia's Digital Health Space: Challenging Information Authority and Reliability",
            "Kartikawangi, Dorien",
            "Le Havre Normandy University, France",
            ["Virtual"]
          ),
          paper(
            "#101",
            "Disinformation about the Oil Crisis: Filter Bubbles and Confirmation Bias in Social Media among South Korea's Gen-Z",
            "Chan, Steve K.L.",
            "Keimyung University, South Korea",
            ["Virtual"]
          ),
          paper(
            "#215",
            "Silencing the Rational: Affective Publics and Power Imbalances in the Digital Discourse of Indonesia's Free Nutritious Meal Program",
            "Hanifah, Adenovi",
            "Universitas Gadjah Mada, Indonesia",
            ["Virtual"]
          ),
        ],
      },
      {
        room: "ROOM B (Budsaba)",
        title:
          "D2 | Communicating Green Energy and Sustainability in ASEAN",
        special: special(`
          <p class="eyebrow">Australia-Asean Network Special Session</p>
          <p>This special panel explores the role of communication, policy, science, and public engagement in advancing green energy and sustainability across ASEAN. The session is connected to the Aus4ASEAN Fellowship, an initiative by the Australian Government Department of Foreign Affairs and Trade (DFAT) supporting emerging regional leaders working on sustainability, climate resilience, and innovation.</p>
          <p>The panel highlights how communication and media can help bridge scientific knowledge, policy discussions, and public understanding in the transition toward sustainable energy futures.</p>
          <p><strong>Speakers</strong><br>1. Alongkorn Parivudhiphongs, Associate Professor and Deputy Dean, Faculty of Communication Arts, Chulalongkorn University<br>2. Amornrat Limmanee, Team Leader, Solar Photovoltaic Technology Research Team, National Energy Technology Center (ENTEC), National Science and Technology Development Agency<br>3. Cleodette Latagan Lagata, Environmental Science Department, Ateneo de Manila University<br>4. Karnklon Raktham, Head of Communications, United Nations Development Programme</p>
          <p><strong>Moderator</strong><br>Ms. Suriwassa Thanyanattawit</p>
        `),
      },
      {
        room: "ROOM C (Rodsukon)",
        title:
          "D3 | The Canon Was Never Neutral: Decolonial Methods, Cultural Memory, and Knowledge from the Global South",
        papers: [
          paper(
            "#142",
            "Komunikograpiya: A Methodological Framework for Filipino Communication Research",
            "Villacastin, Juven Nino",
            "University of Hawaii at Manoa, USA"
          ),
          paper(
            "#100",
            "Making Sense of Letters: The Reframing of the Javanese Script through Multisensory Communication Design",
            "Turangan, Jeremia",
            "Chulalongkorn University, Thailand"
          ),
          paper(
            "#150",
            "Development of Thai Tourism Communication and Representation: A Case Study of Osotho Magazine",
            "Cheyjunya, Chavisa",
            "Chulalongkorn University, Thailand"
          ),
          paper(
            "#176",
            "Language and Journalistic Style in a Rapidly Expanding English-Language Media: A Professional Discourse Perspective on Nepal",
            "Adhikari, Dharma",
            "Xi'an Jiaotong-Liverpool University, China"
          ),
          paper(
            "#151",
            "Political Identity as a Driver of Inadvertent Disinformation Sharing: Evidence from Thailand and Implications for Civic Participation",
            "Sittijinda, Sucheewa",
            "Chulalongkorn University, Thailand"
          ),
        ],
      },
      {
        room: "ROOM D (Karaked)",
        title:
          "D4 | Add to Cart, Add to Culture: Digital Media, Youth Consumption, and the Platform Economy in Asia",
        papers: [
          paper(
            "#171",
            "Media Role in Promoting Y-Series as Soft Power through Online News and Online Conversation",
            "Puntakarnkul, Chonnikarn",
            "Chulalongkorn University, Thailand"
          ),
          paper(
            "#107",
            "The Influence of Social Media Content on Vietnamese Gen Z's Domestic Tourism Intention: The Mediating Roles of Flow Experience and Destination Envy",
            "Hoang, Thu-Trang and Duc-Phuc Nguyen",
            "Diplomatic Academy of Viet Nam, Vietnam"
          ),
          paper(
            "#154",
            "The Influence of the BookTok Media Community on the Book Purchasing Decision Behavior of Thai Readers",
            "Senawongse, Pasin",
            "Srinakharinwirot University, Thailand"
          ),
          paper(
            "#187",
            "Narrative Strategies for Bangkok Street Food on YouTube: A Case Study of 'BANGKOKCIAGA'",
            "Prapaiboon, Preechaphol",
            "Srinakharinwirot University, Thailand"
          ),
          paper(
            "#206",
            "Designing for Discovery Beyond Virality: A Mobile Platform for Cultural-Heritage Tourism in Nepal",
            "Panta, Oshin and Chongvongruk, Jidapa",
            "Chulalongkorn University, Thailand"
          ),
          paper(
            "#164",
            "Factors Influencing the Willingness to Pay for Sustainable Fashion among Generation Z",
            "Kontong, Tatkamon",
            "Srinakharinwirot University, Thailand"
          ),
        ],
      },
    ],
  },
};

function esc(value) {
  return String(value ?? "")
    .replaceAll("&", "&amp;")
    .replaceAll("<", "&lt;")
    .replaceAll(">", "&gt;")
    .replaceAll('"', "&quot;");
}

function logoLockup(extraClass = "") {
  return `
    <div class="logo-lockup ${extraClass}" aria-label="conference logo lockup">
      <img src="${assets.hub}" alt="ICA Bangkok Regional Hub logo">
      <img src="${assets.ica}" alt="International Communication Association logo">
      <img src="${assets.chula}" alt="Chulalongkorn University logo">
    </div>
  `;
}

function page(num, title, content, klass = "") {
  return `
    <section class="page ${klass}">
      ${klass.includes("cover") ? "" : `
        <header class="page-header">
          <div>
            <div class="running-kicker">ICA Regional Hub Thailand 2026</div>
            <div class="running-title">${esc(title)}</div>
          </div>
          ${logoLockup("mini")}
        </header>
      `}
      <main class="content">${content}</main>
      <footer class="page-footer">
        <span>${status}</span>
        <span>Page ${num}</span>
      </footer>
    </section>
  `;
}

function paperHtml(p) {
  if (!p) return `<div class="empty-slot">—</div>`;
  const flags = p.flags?.length
    ? `<span class="tag">${p.flags.map(esc).join("</span><span class=\"tag\">")}</span>`
    : "";
  return `
    <article class="paper">
      <div class="paper-meta"><span class="paper-id">${esc(p.id)}</span>${flags}</div>
      <div class="paper-title">${esc(p.title)}</div>
      <div class="paper-authors">${esc(p.authors)}</div>
      <div class="paper-affiliation">${esc(p.affiliation)}</div>
    </article>
  `;
}

function sessionTable(key) {
  const s = sessions[key];
  const maxRows = Math.max(...s.rooms.map((room) => room.special ? 1 : room.papers.length));
  const tableRows = Array.from({ length: maxRows }, (_, index) => {
    const cells = s.rooms.map((room) => {
      if (room.special) {
        return index === 0
          ? `<td class="special-cell" rowspan="${maxRows}">${room.special.html}</td>`
          : "";
      }
      return `<td>${paperHtml(room.papers[index])}</td>`;
    }).join("");
    return `<tr><th scope="row">Paper ${index + 1}</th>${cells}</tr>`;
  }).join("");

  return `
    <section class="session-block">
      <div class="session-heading">
        <div>
          <span class="session-label">${esc(s.label)}</span>
          <h2>${esc(s.time)}</h2>
        </div>
        <span class="venue-pill">Mandarin Hotel Bangkok</span>
      </div>
      <table class="session-table">
        <thead>
          <tr>
            <th class="slot-head">Slot</th>
            ${s.rooms.map((room) => `<th><span>${esc(room.room)}</span><strong>${esc(room.title)}</strong></th>`).join("")}
          </tr>
        </thead>
        <tbody>${tableRows}</tbody>
      </table>
    </section>
  `;
}

function agendaBand(items) {
  return `
    <div class="agenda-band">
      ${items.map((item) => `
        <div class="agenda-item">
          <time>${esc(item.time)}</time>
          <strong>${esc(item.title)}</strong>
          <span>${esc(item.place)}</span>
        </div>
      `).join("")}
    </div>
  `;
}

const pages = [
  page(1, "Cover", `
    <div class="cover-panel">
      <div class="cover-identity" aria-label="conference logo lockup">
        <img src="${assets.ica}" alt="International Communication Association logo">
        <img src="${assets.hub}" alt="ICA Bangkok Regional Hub Elephant">
        <img src="${assets.chula}" alt="Chulalongkorn University logo">
      </div>

      <div class="cover-copy">
        <p class="cover-kicker">76th Annual ICA Regional Hub Thailand</p>
        <h1>ICA-TH 2026<br>Detailed Program</h1>
        <p class="cover-subtitle">International Communication Association Regional Hub Thailand</p>
      </div>

      <div class="cover-facts">
        <div class="fact-chip wide"><strong>5 June 2026 / Faculty of Communication Arts<br>Chulalongkorn University</strong></div>
        <div class="fact-chip"><strong>6-7 June 2026</strong></div>
        <div class="fact-chip"><strong>Mandarin Hotel Bangkok</strong></div>
        <div class="fact-chip"><strong>Bangkok, Thailand</strong></div>
      </div>

      <div class="cover-program">
        <article>
          <span>Day 0</span>
          <p>Special Forum conducted in Thai with a summary in English. Guided University Tour. Networking Activities.</p>
        </article>
        <article>
          <span>Day 1-2</span>
          <p>Plenary Panels, Masterclass, Special Talks, and paper presentations.</p>
        </article>
      </div>

      <div class="cover-label">
        <div class="gold-rule"></div>
        <h2>Conference Program</h2>
        <p>${status}</p>
      </div>

      <div class="cover-bottom">Detailed Program | ICA Regional Hub Thailand 2026</div>
    </div>
  `, "cover"),

  page(2, "Day 1 Morning", `
    <div class="day-title">
      <div><span>Day 1</span><h1>Saturday, 6 June 2026</h1></div>
      <p>Mandarin Hotel Bangkok · Plenary Hall + Rooms 1–4</p>
    </div>
    ${agendaBand([
      { time: "08:30-09:00", title: "Registration & Networking Coffee", place: "Foyer" },
      { time: "09:00-09:30", title: "Opening Ceremony", place: "Plenary Hall / ROOM A (Mandarin A)" },
      { time: "09:30-10:30", title: "Plenary Panel: Asian Perspectives on Communication and Inequalities", place: "Plenary Hall / ROOM A" },
      { time: "10:30-11:00", title: "Coffee Break", place: "Foyer" },
    ])}
    <div class="opening-card">
      <div>
        <time>09:00-09:30</time>
        <h2>Opening Ceremony</h2>
      </div>
      <p>Presided over by the President of Chulalongkorn University, jointly with the Dean of the Faculty of Communication Arts, the President of ANPOR, and a video from the President of the International Communication Association (ICA).</p>
    </div>
    <div class="speaker-grid">
      <article>
        <span>Plenary Speaker 1</span>
        <h2>Prof. Dr. Masato Kajimoto, PhD</h2>
        <p>Professor of Practice in Journalism, University of Hong Kong</p>
        <strong>From Fact-Checking to Information Integrity: Refining Media Literacy Education in the AI-Powered Ecosystem</strong>
      </article>
      <article>
        <span>Plenary Speaker 2</span>
        <h2>Prof. Dr. Phouphet Kyophilavong</h2>
        <p>National University of Laos</p>
        <strong>The Impact of Digital Transformation on the Economy in the Greater Mekong Subregion (GMS)</strong>
      </article>
      <article>
        <span>Plenary Speaker 3</span>
        <h2>Assoc. Prof. Dr. Long TV Nguyen</h2>
        <p>School of Communication & Design, RMIT International University, Vietnam</p>
        <strong>Health Communication in the Platform Society: Trust, Participation, and Digital Engagement</strong>
      </article>
    </div>
  `, "program-page plenary-page"),

  page(3, "Parallel Session A", `
    <div class="day-title tight">
      <div><span>Day 1</span><h1>Parallel Session A</h1></div>
      <p>11:00-12:30 · Mandarin Hotel Bangkok</p>
    </div>
    ${sessionTable("A")}
    ${agendaBand([
      { time: "12:30-13:30", title: "Lunch Break", place: "Mandarin Hotel Bangkok" },
    ])}
  `, "program-page session-only session-a-only"),

  page(4, "Day 1 Afternoon", `
    <div class="day-title tight">
      <div><span>Day 1</span><h1>Saturday, 6 June 2026 · Afternoon</h1></div>
      <p>Lunch 12:30-13:30 · Coffee 15:00-15:30</p>
    </div>
    ${agendaBand([
      { time: "12:30-13:30", title: "Lunch Break", place: "Mandarin Hotel Bangkok" },
      { time: "13:30-15:00", title: "Hub-to-hub Roundtable", place: "Plenary Hall / ROOM A" },
      { time: "15:00-15:30", title: "Coffee Break", place: "Foyer" },
      { time: "17:00-17:30", title: "Day 1 Highlights & Recap", place: "Plenary Hall (Room A)" },
      { time: "18:30-21:00", title: "ICA Thailand Cultural Evening", place: "By invitation only" },
    ])}
    <div class="roundtable-feature">
      <div>
        <time>13:30-15:00</time>
        <h2>Hub-to-hub Roundtable Representing Five ICA Regional Hubs</h2>
        <p>Bringing together ICA hub representatives from the Philippines, Nigeria, Indonesia, Thailand, and New Zealand to discuss algorithmic power, decolonial communication, and the politics of voice in the Global South.</p>
      </div>
      <ul>
        <li><strong>Marco M. Polo — Philippines</strong><span>Associate Professor, De La Salle University–Dasmariñas | PACE & AMIC</span></li>
        <li><strong>Ekaete George — Nigeria</strong><span>Development Communication Researcher | ICA Nigeria Chapter</span></li>
        <li><strong>Dorien Kartigawangi — Indonesia</strong><span>Scholar of AI, epistemic coloniality, and non-Western knowledge systems</span></li>
        <li><strong>Mohan J. Dutta — New Zealand</strong><span>Director of CARE, Massey University | Culture-centered communication scholar</span></li>
        <li><strong>Alongkorn Parivudhiphongs — Thailand</strong><span>Deputy Dean, Faculty of Communication Arts, Chulalongkorn University</span></li>
      </ul>
      <p class="moderator"><strong>Moderator</strong> Jerwin S. Borres, Assistant Prof., University of Science and Technology of Southern Philippines (USTP)</p>
    </div>
  `, "program-page roundtable-page"),

  page(5, "Parallel Session B", `
    <div class="day-title tight">
      <div><span>Day 1</span><h1>Parallel Session B</h1></div>
      <p>15:30-17:00 · Mandarin Hotel Bangkok</p>
    </div>
    ${sessionTable("B")}
    ${agendaBand([
      { time: "17:00-17:30", title: "Day 1 Highlights & Recap", place: "Plenary Hall (Room A)" },
      { time: "18:30-21:00", title: "ICA Thailand Cultural Evening (By Invitation only)", place: "Hosted Dinner & Cultural Reception" },
    ])}
  `, "program-page session-only session-b-only"),

  page(6, "Day 2 Morning", `
    <div class="day-title">
      <div><span>Day 2</span><h1>Sunday, 7 June 2026</h1></div>
      <p>Mandarin Hotel Bangkok · Conference Rooms (A–D)</p>
    </div>
    ${agendaBand([
      { time: "08:30-09:00", title: "Registration & Networking Coffee", place: "Foyer" },
      { time: "10:30-11:00", title: "Coffee Break", place: "Foyer" },
    ])}
    ${sessionTable("C")}
  `, "program-page session-only day2-morning"),

  page(7, "Masterclass & Outreach", `
    <div class="day-title">
      <div><span>Day 2</span><h1>Masterclass & Outreach</h1></div>
      <p>Plenary Hall / ROOM A (Mandarin A)</p>
    </div>
    <div class="feature-grid">
      <article class="feature-card">
        <time>11:00-11:45</time>
        <p class="eyebrow">Masterclass · Session 1</p>
        <h2>Prof. Sung Kyum Cho</h2>
        <h3>From Prompt to Verified Findings:<br>AI for Research Data Analysis and Validation in Communication Studies</h3>
      </article>
      <article class="feature-card secondary">
        <time>11:45-12:30</time>
        <p class="eyebrow">Masterclass · Session 2</p>
        <h2>Prof. Mohan J. Dutta</h2>
        <h3>CARE Methodology:<br>Culture-Centered Approach to Research and Evaluation:<br>Voice, Reflexivity, and Structural Transformation</h3>
      </article>
    </div>
    <div class="outreach-card">
      <time>13:30-15:00</time>
      <p class="eyebrow">Outreach</p>
      <h2>ICA Handshake from Cape Town</h2>
      <p>Showcases and highlights from ICA family · Arranged by ICA Secretariat</p>
    </div>
    <div class="masterclass-note">All Masterclass sessions are virtual. Lunch Break: 12:30-13:30 · Coffee Break: 15:00-15:30.</div>
  `, "feature-page"),

  page(8, "Day 2 Afternoon", `
    <div class="day-title tight">
      <div><span>Day 2</span><h1>Sunday, 7 June 2026 · Afternoon</h1></div>
      <p>Mandarin Hotel Bangkok · Rooms A–D</p>
    </div>
    ${sessionTable("D")}
    <div class="closing-band">
      <time>17:00-17:30</time>
      <strong>Closing Forum & Legacy Building</strong>
      <span>Reflections · Future Directions · Regional Research Agenda · Plenary Hall / ROOM A</span>
    </div>
  `, "program-page day2-afternoon"),

  page(9, "Organized By", `
    <div class="section-title sponsor-title">
      <p>Organized By</p>
      <h1>Conference hosts, collaborators, and partners</h1>
    </div>
    <div class="sponsor-layout">
      <section class="sponsor-section major">
        <h2>Organized By</h2>
        <div class="sponsor-grid two">
          <figure><img src="${assets.faculty}" alt="Faculty of Communication Arts, Chulalongkorn University"><figcaption>Faculty of Communication Arts<br>Chulalongkorn University</figcaption></figure>
          <figure><img src="${assets.hub}" alt="ICA Regional Hub Thailand"><figcaption>ICA Regional Hub Thailand</figcaption></figure>
        </div>
      </section>
      <section class="sponsor-section collaboration">
        <h2>In Collaboration With</h2>
        <figure><img src="${assets.thaiMediaFund}" alt="Thai Media Fund"><figcaption>Thai Media Fund</figcaption></figure>
      </section>
      <section class="sponsor-section partners">
        <h2>In Partnership With</h2>
        <div class="partner-grid">
          <figure><img src="${assets.anpor}" alt="ANPOR"><figcaption>ANPOR</figcaption></figure>
          <figure><img src="${assets.nida}" alt="GSCM NIDA"><figcaption>GSCM NIDA</figcaption></figure>
          <figure><img src="${assets.jc}" alt="Faculty of Journalism and Mass Communication"><figcaption>Faculty of Journalism and Mass Communication</figcaption></figure>
          <figure><img src="${assets.damrong}" alt="Damrong Rajanubhab Institute"><figcaption>Damrong Rajanubhab Institute</figcaption></figure>
          <figure><img src="${assets.swu}" alt="College of Social Communication Innovation"><figcaption>College of Social Communication Innovation</figcaption></figure>
          <figure><img src="${assets.cmct}" alt="C.M.C.T."><figcaption>C.M.C.T.</figcaption></figure>
          <figure><img src="${assets.psu}" alt="PSU Faculty of Communication Sciences"><figcaption>PSU Faculty of Communication Sciences</figcaption></figure>
        </div>
      </section>
    </div>
  `, "sponsor-page"),
];

const css = `
  :root {
    --primary: #183A5A;
    --secondary: #D65C93;
    --accent: #E8B548;
    --ink: #192532;
    --muted: #607083;
    --line: #D8E0E8;
    --soft: #F4F7FA;
    --soft-pink: #FCF0F6;
    --soft-gold: #FFF7E3;
  }
  @page { size: A4 landscape; margin: 0; }
  * { box-sizing: border-box; }
  html, body { margin: 0; padding: 0; }
  body {
    background: #d8dde5;
    color: var(--ink);
    font-family: "Aptos", "Inter", "Helvetica Neue", Arial, sans-serif;
    -webkit-print-color-adjust: exact;
    print-color-adjust: exact;
  }
  .page {
    position: relative;
    width: 297mm;
    height: 210mm;
    margin: 0 auto;
    overflow: hidden;
    overflow: clip;
    contain: layout paint;
    background: white;
    page-break-after: always;
    break-after: page;
  }
  .page:last-child,
  .page:last-of-type {
    page-break-after: auto;
    break-after: auto;
  }
  .content {
    position: absolute;
    inset: 22mm 13mm 14mm 13mm;
  }
  .page-header {
    position: absolute;
    top: 7mm;
    left: 13mm;
    right: 13mm;
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding-bottom: 4mm;
    border-bottom: 0.3mm solid var(--line);
  }
  .running-kicker {
    color: var(--secondary);
    font-size: 7.4pt;
    font-weight: 800;
    letter-spacing: 0;
    text-transform: uppercase;
  }
  .running-title {
    margin-top: 1.2mm;
    color: var(--primary);
    font-size: 13.4pt;
    font-weight: 800;
  }
  .page-footer {
    position: absolute;
    bottom: -10mm;
    left: 13mm;
    right: 13mm;
    display: flex;
    justify-content: space-between;
    color: #52657a;
    font-size: 7.4pt;
    border-top: 0.25mm solid var(--line);
    padding-top: 2.2mm;
  }
  .logo-lockup {
    display: flex;
    gap: 5mm;
    align-items: center;
  }
  .logo-lockup img {
    max-width: 34mm;
    max-height: 14mm;
    object-fit: contain;
  }
  .logo-lockup img:first-child { max-width: 18mm; }
  .logo-lockup img:nth-child(2) { max-width: 32mm; }
  .logo-lockup img:nth-child(3) { max-width: 38mm; }
  .logo-lockup.mini { gap: 3.5mm; }
  .logo-lockup.mini img { max-height: 8.5mm; max-width: 24mm; }
  .logo-lockup.mini img:first-child { max-width: 11mm; }
  .logo-lockup.mini img:nth-child(3) { max-width: 29mm; }

  .cover {
    background:
      radial-gradient(circle at 83% 18%, rgba(214, 92, 147, .18), transparent 31%),
      linear-gradient(180deg, #1f456b 0%, var(--primary) 54%, #14314f 100%);
  }
  .cover::before {
    content: none;
  }
  .cover::after {
    content: "";
    position: absolute;
    left: 0;
    right: 0;
    bottom: 8mm;
    height: 1.2mm;
    background: var(--accent);
  }
  .cover .content { inset: 0; }
  .cover .page-footer {
    color: rgba(255,255,255,.78);
    border-top: 0;
    bottom: 8mm;
    left: auto;
    right: 21mm;
    justify-content: flex-end;
    width: auto;
    padding: 0;
    font-size: 10pt;
  }
  .cover .page-footer span:first-child { display: none; }
  .cover .page-footer span:last-child { display: none; }
  .cover-panel {
    position: absolute;
    inset: 21mm 22mm 18mm 25mm;
    color: white;
  }
  .cover-identity {
    display: flex;
    align-items: center;
    gap: 4.5mm;
    margin-bottom: 15mm;
  }
  .cover-identity img {
    display: block;
    object-fit: contain;
    max-height: 21mm;
    max-width: 53mm;
  }
  .cover-identity img:nth-child(2) {
    width: 21mm;
    height: 21mm;
    object-fit: cover;
  }
  .cover-identity img:nth-child(3) {
    max-width: 58mm;
    max-height: 22mm;
  }
  .cover-kicker {
    color: var(--accent);
    font-size: 13pt;
    line-height: 1;
    font-weight: 500;
    text-transform: uppercase;
    margin: 0 0 7mm;
  }
  .cover h1 {
    max-width: 214mm;
    color: white;
    font-size: 38pt;
    font-weight: 400;
    line-height: 1.05;
    margin: 0;
    letter-spacing: 0;
  }
  .cover-subtitle {
    max-width: 190mm;
    color: rgba(255,255,255,.78);
    font-size: 17pt;
    line-height: 1.25;
    margin: 6mm 0 13mm;
  }
  .cover-facts {
    display: grid;
    grid-template-columns: 82mm 44mm 62mm 49mm;
    gap: 5mm;
    align-items: center;
    margin-bottom: 0;
  }
  .fact-chip {
    min-height: 12mm;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 2mm 4.5mm;
    border-radius: 99px;
    background: rgba(116, 154, 194, .55);
    color: white;
    text-align: center;
  }
  .fact-chip strong {
    font-size: 10pt;
    font-weight: 500;
    line-height: 1.15;
  }
  .cover-program {
    display: grid;
    grid-template-columns: 82mm 96mm;
    gap: 19mm;
    margin: 5.5mm 0 0 4.5mm;
  }
  .cover-program article span {
    display: none;
  }
  .cover-program p {
    margin: 0;
    color: rgba(255,255,255,.9);
    font-size: 8.9pt;
    font-weight: 700;
    line-height: 1.25;
  }
  .cover-label {
    position: absolute;
    left: 0;
    bottom: 5mm;
    margin-top: 0;
  }
  .gold-rule {
    width: 72mm;
    height: 1.4mm;
    background: var(--accent);
    margin-bottom: 7mm;
  }
  .cover-label h2 {
    margin: 0;
    color: white;
    font-size: 26pt;
    font-weight: 400;
    line-height: 1.08;
  }
  .cover-label p {
    margin: 4mm 0 0;
    color: rgba(255,255,255,.78);
    font-size: 12.5pt;
  }
  .cover-bottom {
    position: absolute;
    right: 0;
    bottom: 0;
    color: rgba(255,255,255,.78);
    font-size: 10pt;
  }

  .section-title p,
  .day-title span,
  .eyebrow,
  .session-label {
    margin: 0;
    color: var(--secondary);
    font-size: 8pt;
    font-weight: 900;
    text-transform: uppercase;
  }
  .section-title h1,
  .day-title h1 {
    margin: 1.5mm 0 0;
    color: var(--primary);
    font-size: 24pt;
    line-height: 1.08;
  }
  .overview-timeline {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 9mm;
    margin-top: 12mm;
  }
  .timeline-card {
    position: relative;
    min-height: 94mm;
    padding: 9mm;
    border: .4mm solid var(--line);
    border-radius: 4mm;
    background: linear-gradient(180deg, white, var(--soft));
  }
  .timeline-card.primary {
    background: linear-gradient(180deg, var(--primary), #0f2942);
    color: white;
    border-color: var(--primary);
  }
  .day-marker {
    display: inline-flex;
    align-items: center;
    min-width: 26mm;
    height: 9mm;
    padding: 0 3mm;
    background: var(--accent);
    color: var(--primary);
    font-size: 8pt;
    font-weight: 900;
    text-transform: uppercase;
  }
  .timeline-card h2 {
    margin: 8mm 0 2mm;
    font-size: 26pt;
    color: inherit;
  }
  .timeline-card .venue {
    min-height: 13mm;
    margin: 0 0 7mm;
    color: inherit;
    font-size: 12pt;
    font-weight: 800;
    line-height: 1.28;
  }
  .timeline-card ul {
    margin: 0;
    padding-left: 5mm;
    font-size: 11.5pt;
    line-height: 1.55;
  }
  .overview-note {
    margin-top: 11mm;
    display: flex;
    gap: 5mm;
    align-items: center;
    padding: 5mm 6mm;
    background: var(--soft-gold);
    border-left: 1.1mm solid var(--accent);
    font-size: 11pt;
  }
  .overview-note strong { color: var(--primary); }
  .day-title {
    display: flex;
    justify-content: space-between;
    align-items: end;
    gap: 10mm;
    margin-bottom: 4mm;
  }
  .day-title.tight { margin-bottom: 3mm; }
  .day-title h1 { font-size: 20pt; }
  .day-title p {
    margin: 0;
    color: var(--muted);
    font-size: 10pt;
    font-weight: 700;
    text-align: right;
  }
  .agenda-band {
    display: grid;
    grid-auto-flow: column;
    grid-auto-columns: 1fr;
    gap: 2.5mm;
    margin: 0 0 3.4mm;
  }
  .agenda-item {
    min-height: 13mm;
    padding: 2.2mm 2.8mm;
    background: var(--soft);
    border-left: .85mm solid var(--accent);
  }
  .agenda-item time,
  .closing-band time,
  .roundtable-card time,
  .feature-card time,
  .outreach-card time {
    display: block;
    color: var(--secondary);
    font-size: 7.5pt;
    font-weight: 900;
  }
  .agenda-item strong {
    display: block;
    color: var(--primary);
    font-size: 8.7pt;
    line-height: 1.1;
    margin-top: .6mm;
  }
  .agenda-item span {
    color: var(--muted);
    display: block;
    font-size: 7.5pt;
    line-height: 1.15;
    margin-top: .5mm;
  }
  .plenary-card,
  .opening-card,
  .speaker-grid article,
  .roundtable-card,
  .closing-band,
  .outreach-card,
  .masterclass-note {
    border: .3mm solid var(--line);
    border-radius: 3mm;
    background: #fff;
  }
  .plenary-card {
    display: grid;
    grid-template-columns: 51mm 1fr;
    gap: 5mm;
    padding: 3.2mm 4mm;
    margin-bottom: 3.2mm;
  }
  .plenary-card h2 {
    margin: 0;
    color: var(--primary);
    font-size: 11.5pt;
    line-height: 1.18;
  }
  .plenary-card ol {
    margin: 0;
    padding-left: 5mm;
    columns: 3;
    column-gap: 6mm;
    font-size: 7.2pt;
    line-height: 1.25;
  }
  .plenary-card li { break-inside: avoid; margin-bottom: 1.2mm; }
  .opening-card {
    display: grid;
    grid-template-columns: 43mm 1fr;
    gap: 7mm;
    align-items: center;
    padding: 6mm 7mm;
    margin: 7mm 0 7mm;
    background: var(--soft-gold);
    border-left: 1.3mm solid var(--accent);
  }
  .opening-card h2 {
    margin: 1mm 0 0;
    color: var(--primary);
    font-size: 18pt;
  }
  .opening-card p {
    margin: 0;
    color: #40566f;
    font-size: 12pt;
    line-height: 1.38;
  }
  .speaker-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 5mm;
  }
  .speaker-grid article {
    min-height: 59mm;
    padding: 6mm;
    background: linear-gradient(180deg, #fff, var(--soft));
  }
  .speaker-grid span {
    display: block;
    color: var(--secondary);
    font-size: 7.5pt;
    font-weight: 900;
    text-transform: uppercase;
    margin-bottom: 3mm;
  }
  .speaker-grid h2 {
    margin: 0 0 2mm;
    color: var(--primary);
    font-size: 15pt;
    line-height: 1.1;
  }
  .speaker-grid p {
    min-height: 13mm;
    margin: 0 0 4mm;
    color: var(--muted);
    font-size: 9pt;
    line-height: 1.25;
  }
  .speaker-grid strong {
    color: #213850;
    font-size: 10pt;
    line-height: 1.28;
  }
  .roundtable-card {
    display: grid;
    grid-template-columns: 66mm 1fr 83mm;
    gap: 4mm;
    align-items: center;
    padding: 3.2mm 4mm;
    margin-bottom: 3mm;
    background: var(--soft-gold);
    border-color: #f0dfad;
  }
  .roundtable-card h2 {
    color: var(--primary);
    font-size: 11pt;
    margin: .5mm 0 0;
    line-height: 1.15;
  }
  .roundtable-card p {
    margin: 0;
    font-size: 7.4pt;
    line-height: 1.24;
  }
  .roundtable-feature {
    display: grid;
    grid-template-columns: .92fr 1.08fr;
    gap: 7mm;
    margin-top: 7mm;
    padding: 8mm;
    border-radius: 4mm;
    border: .3mm solid #eddba9;
    border-left: 1.4mm solid var(--accent);
    background: var(--soft-gold);
  }
  .roundtable-feature time {
    display: block;
    color: var(--secondary);
    font-size: 8pt;
    font-weight: 900;
  }
  .roundtable-feature h2 {
    margin: 2mm 0 4mm;
    color: var(--primary);
    font-size: 21pt;
    line-height: 1.08;
  }
  .roundtable-feature p {
    margin: 0;
    color: #40566f;
    font-size: 11.2pt;
    line-height: 1.42;
  }
  .roundtable-feature ul {
    margin: 0;
    padding: 0;
    list-style: none;
    display: grid;
    gap: 2.4mm;
  }
  .roundtable-feature li {
    padding: 2.5mm 3mm;
    background: white;
    border: .25mm solid #eadfbf;
    border-radius: 2mm;
  }
  .roundtable-feature li strong {
    display: block;
    color: var(--primary);
    font-size: 9.5pt;
  }
  .roundtable-feature li span {
    display: block;
    color: var(--muted);
    font-size: 8.3pt;
    line-height: 1.2;
    margin-top: .6mm;
  }
  .roundtable-feature .moderator {
    grid-column: 1 / -1;
    padding: 3mm 4mm;
    background: white;
    border-radius: 2mm;
    border: .25mm solid #eadfbf;
    font-size: 10pt;
  }

  .session-block { width: 100%; }
  .session-heading {
    display: flex;
    justify-content: space-between;
    align-items: end;
    margin-bottom: 2mm;
  }
  .session-heading h2 {
    margin: .5mm 0 0;
    color: var(--primary);
    font-size: 15pt;
  }
  .venue-pill {
    padding: 1.5mm 3mm;
    border-radius: 99px;
    color: var(--primary);
    background: var(--soft);
    font-size: 7.8pt;
    font-weight: 800;
  }
  .session-table {
    width: 100%;
    border-collapse: collapse;
    table-layout: fixed;
    font-size: 6.65pt;
    line-height: 1.17;
  }
  .session-table th,
  .session-table td {
    border: .24mm solid #d7e0e8;
    padding: 1.15mm 1.25mm;
    vertical-align: top;
  }
  .session-table thead th {
    background: var(--primary);
    color: white;
    text-align: left;
    font-weight: 800;
    vertical-align: middle;
  }
  .session-table thead th span {
    display: block;
    color: #d7eafc;
    font-size: 6.4pt;
    margin-bottom: 1mm;
  }
  .session-table thead th strong {
    display: block;
    font-size: 7pt;
    line-height: 1.16;
  }
  .session-table .slot-head,
  .session-table tbody th {
    width: 13mm;
    text-align: center;
  }
  .session-table tbody th {
    color: var(--primary);
    background: #edf3f7;
    font-size: 6.35pt;
    font-weight: 900;
    vertical-align: middle;
  }
  .session-table tbody tr:nth-child(even) td { background: #fbfcfd; }
  .paper { break-inside: avoid; }
  .paper-meta {
    display: flex;
    gap: 1.2mm;
    align-items: center;
    margin-bottom: .8mm;
  }
  .paper-id {
    display: inline-block;
    color: var(--secondary);
    font-weight: 900;
  }
  .tag {
    display: inline-block;
    padding: .25mm 1.1mm;
    border-radius: 99px;
    background: var(--soft-pink);
    color: var(--secondary);
    font-size: 5.7pt;
    font-weight: 900;
    text-transform: uppercase;
  }
  .paper-title {
    color: #1b3148;
    font-weight: 800;
  }
  .paper-authors {
    margin-top: .8mm;
    color: #293b4f;
    font-weight: 700;
  }
  .paper-affiliation {
    margin-top: .35mm;
    color: #667486;
  }
  .special-cell {
    background: #fff9ec !important;
    font-size: 6.3pt;
    line-height: 1.16;
  }
  .special-cell p { margin: 0 0 1.7mm; }
  .special-cell ol {
    margin: .8mm 0 1.8mm 4mm;
    padding-left: 2.8mm;
  }
  .special-cell li { margin-bottom: 1.2mm; }
  .empty-slot {
    color: #b2bdc8;
    text-align: center;
    padding-top: 3mm;
  }
  .day1-afternoon .session-table { font-size: 6.25pt; line-height: 1.12; }
  .day1-afternoon .special-cell { font-size: 5.9pt; line-height: 1.1; }
  .day2-afternoon .session-table { font-size: 6.35pt; line-height: 1.13; }
  .day2-afternoon .special-cell { font-size: 6.05pt; line-height: 1.12; }
  .compact-top .session-table { font-size: 6.65pt; }
  .session-only .content { bottom: 13mm; }
  .session-only .day-title { margin-bottom: 2.2mm; }
  .session-only .session-table { font-size: 6.72pt; line-height: 1.15; }
  .session-only .session-table th,
  .session-only .session-table td { padding: 1.05mm 1.15mm; }
  .session-only .special-cell { font-size: 6.2pt; line-height: 1.13; }
  .session-a-only .agenda-band { margin-top: 3mm; }
  .session-b-only .session-table { font-size: 6.16pt; line-height: 1.08; }
  .session-b-only .session-table th,
  .session-b-only .session-table td { padding: .78mm .95mm; }
  .session-b-only .special-cell { font-size: 5.48pt; line-height: 1.06; }
  .session-b-only .agenda-band { margin-top: 2.7mm; }
  .day2-morning .agenda-band { margin-bottom: 2mm; }
  .day2-morning .agenda-item { min-height: 10mm; padding: 1.6mm 2.5mm; }
  .day2-morning .session-table { font-size: 6.15pt; line-height: 1.1; }
  .day2-morning .session-table th,
  .day2-morning .session-table td { padding: .85mm 1mm; }
  .day2-morning .special-cell { font-size: 5.55pt; line-height: 1.08; }

  .feature-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 7mm;
    margin-top: 12mm;
  }
  .feature-card {
    min-height: 67mm;
    padding: 8mm;
    border-radius: 4mm;
    background: var(--primary);
    color: white;
    border-top: 2mm solid var(--accent);
  }
  .feature-card.secondary {
    background: #223145;
    border-top-color: var(--secondary);
  }
  .feature-card .eyebrow { color: var(--accent); margin-top: 6mm; }
  .feature-card.secondary .eyebrow { color: #f1b8d2; }
  .feature-card h2 {
    margin: 2mm 0 6mm;
    font-size: 20pt;
    line-height: 1.05;
    color: white;
  }
  .feature-card h3 {
    margin: 0;
    font-size: 14pt;
    line-height: 1.28;
    color: rgba(255,255,255,.93);
  }
  .outreach-card {
    margin-top: 8mm;
    padding: 7mm 8mm;
    display: grid;
    grid-template-columns: 34mm 1fr;
    grid-template-rows: auto auto;
    column-gap: 7mm;
    row-gap: 1mm;
    align-items: center;
    background: var(--soft-gold);
    border-left: 1.4mm solid var(--accent);
  }
  .outreach-card time { grid-column: 1; grid-row: 1; }
  .outreach-card .eyebrow { grid-column: 1; grid-row: 2; }
  .outreach-card h2 {
    grid-column: 2;
    grid-row: 1;
    margin: 1mm 0;
    color: var(--primary);
    font-size: 19pt;
  }
  .outreach-card p:last-child {
    grid-column: 2;
    grid-row: 2;
    margin: 0;
    color: var(--muted);
    font-size: 11pt;
  }
  .masterclass-note {
    margin-top: 6mm;
    padding: 4mm 5mm;
    color: var(--primary);
    background: var(--soft);
    font-weight: 800;
    font-size: 10pt;
  }
  .closing-band {
    margin-top: 3.5mm;
    display: grid;
    grid-template-columns: 26mm 68mm 1fr;
    gap: 4mm;
    align-items: center;
    padding: 3mm 4mm;
    background: var(--soft);
  }
  .closing-band strong {
    color: var(--primary);
    font-size: 11pt;
  }
  .closing-band span {
    color: var(--muted);
    font-size: 9.2pt;
  }
  .sponsor-title { margin-bottom: 7mm; }
  .sponsor-layout {
    display: grid;
    grid-template-columns: 1.2fr .8fr;
    grid-template-rows: auto 1fr;
    gap: 6mm;
  }
  .sponsor-section {
    padding: 6mm;
    border: .3mm solid var(--line);
    border-radius: 4mm;
    background: white;
  }
  .sponsor-section h2 {
    margin: 0 0 5mm;
    color: var(--primary);
    font-size: 13pt;
  }
  .sponsor-section.major { background: var(--soft); }
  .sponsor-section.partners {
    grid-column: 1 / -1;
    padding-bottom: 5mm;
  }
  .sponsor-grid,
  .partner-grid {
    display: grid;
    gap: 5mm;
  }
  .sponsor-grid.two { grid-template-columns: 1.4fr .8fr; }
  .partner-grid { grid-template-columns: repeat(7, 1fr); }
  figure {
    margin: 0;
    min-height: 31mm;
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;
    gap: 2.5mm;
    padding: 4mm;
    border-radius: 3mm;
    background: white;
    border: .25mm solid #e0e6ec;
  }
  .partners figure { min-height: 34mm; }
  figure img {
    max-width: 100%;
    max-height: 18mm;
    object-fit: contain;
  }
  .major figure img { max-height: 22mm; }
  .major figure:nth-child(2) img { max-height: 25mm; }
  .collaboration figure img { max-height: 22mm; }
  figcaption {
    color: #46576a;
    font-size: 7.4pt;
    font-weight: 700;
    line-height: 1.15;
    text-align: center;
  }
  @media screen {
    .page { margin: 20px auto; box-shadow: 0 10px 28px rgba(0,0,0,.18); }
  }
`;

const html = `<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>ICA Regional Hub Thailand 2026 Booklet</title>
  <style>${css}</style>
</head>
<body>
  ${pages.join("\n")}
</body>
</html>
`;

writeFileSync(out, html, "utf8");
console.log(out);
