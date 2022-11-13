drop table InterviewedBy;
drop table ImmigrationOfficersWorksIn;
drop table InterviewsMakes;
drop table Creates;
drop table Holds;
drop table Applicants;
drop table TravelHistoryRecordsTravelsBy;
drop table InOut;
drop table StudentVisaVerifiedBy;
drop table WorkVisaSponseredBy;
drop table AsylumRefugeeVisa;
drop table VisaFromIssue;
drop table Applications;
drop table EmbassyConsulates;
drop table ApprovedInstitutions;

CREATE TABLE ApprovedInstitutions (
    InstitutionID          VARCHAR(100),		
    InstitutionName        VARCHAR(100),
    Category               VARCHAR(100), 
    PRIMARY KEY (InstitutionID)
);
grant select on ApprovedInstitutions to public;

CREATE TABLE EmbassyConsulates (
    ECID            VARCHAR(100)            PRIMARY KEY,
    AddressName     VARCHAR(100)            UNIQUE,
    Country         VARCHAR(100)            NOT NULL
);
grant select on EmbassyConsulates to public;

CREATE TABLE Applications (
    ApplicationID           VARCHAR(100)        PRIMARY KEY,
    StatusOfApp             NUMBER(1)           NOT NULL
);
grant select on Applications to public;

CREATE TABLE VisaFromIssue (
    VisaID          VARCHAR(100)        PRIMARY KEY,
    VisaType        VARCHAR(100),
    ApplicationID   VARCHAR(100)        NOT NULL,
    ECID            VARCHAR(100)        NOT NULL,
    IssueDate       DATE,
    FOREIGN KEY (ApplicationID)
        REFERENCES Applications
        ON DELETE CASCADE,
    FOREIGN KEY (ECID)
        REFERENCES EmbassyConsulates
        ON DELETE CASCADE
);
grant select on VisaFromIssue to public;

CREATE TABLE AsylumRefugeeVisa (
    VisaID      VARCHAR(100)        PRIMARY KEY,
    Reason      VARCHAR(100)        NOT NULL,
    FOREIGN KEY (VisaID) REFERENCES VisaFromIssue
        ON DELETE CASCADE
);
grant select on AsylumRefugeeVisa to public;

CREATE TABLE WorkVisaSponseredBy (
    VisaID          VARCHAR(100)        PRIMARY KEY,
    WorkType        VARCHAR(100)        NOT NULL,
    InstitutionID   VARCHAR(100)        NOT NULL,    
    FOREIGN KEY (VisaID)
        REFERENCES VisaFromIssue
        ON DELETE CASCADE, 
    FOREIGN KEY (InstitutionID)
        REFERENCES ApprovedInstitutions
);
grant select on WorkVisaSponseredBy to public;

CREATE TABLE StudentVisaVerifiedBy (
    VisaID          VARCHAR(100)		PRIMARY KEY,
    StudyLevel      VARCHAR(100)        NOT NULL,
    InstitutionID   VARCHAR(100)        NOT NULL,
    FOREIGN KEY (VisaID)
        REFERENCES VisaFromIssue,
    FOREIGN KEY (InstitutionID) 
        REFERENCES ApprovedInstitutions
);
grant select on StudentVisaVerifiedBy to public;

CREATE TABLE InOut (
    Destination     VARCHAR(100),
    Departure       VARCHAR(100),
    InOut           NUMBER(1)               NOT NULL,
    PRIMARY KEY (Destination, Departure)
);
grant select on InOut to public;

CREATE TABLE TravelHistoryRecordsTravelsBy (
    RecordID        VARCHAR(100)        PRIMARY KEY,
    TimeOfTravel    TIMESTAMP           NOT NULL,
    Destination     VARCHAR(100)        NOT NULL,
    Departure       VARCHAR(100)        NOT NULL,
    VisaID          VARCHAR(100)        NOT NULL,
    FOREIGN KEY (VisaID)
        REFERENCES VisaFromIssue,
    FOREIGN KEY (Destination, Departure)
        REFERENCES InOut(Destination, Departure)
);
grant select on TravelHistoryRecordsTravelsBy to public;

CREATE TABLE Applicants (
    ApplicantID             VARCHAR(100)        PRIMARY KEY,
    NameOfApplicants        VARCHAR(100)        NOT NULL,
    Nationality             VARCHAR(100)        NOT NULL,
    DateOfBirth             DATE                NOT NULL
);
grant select on Applicants to public;

CREATE TABLE Holds ( 
    ApplicantID         VARCHAR(100),
    VisaID              VARCHAR(100),
    IssueDate           DATE                NOT NULL,
    ExpirationDate      DATE                NOT NULL,
    PRIMARY KEY (ApplicantID, VisaID),
    FOREIGN KEY (ApplicantID)
        REFERENCES Applicants(ApplicantID),
    FOREIGN KEY (VisaID)
        REFERENCES VisaFromIssue(VisaID)
);
grant select on Holds to public;


CREATE TABLE Creates (
    ApplicantID             VARCHAR(100),
    ApplicationID           VARCHAR(100),
    CreateDate              DATE                    NOT NULL,
    PRIMARY KEY (ApplicantID, ApplicationID),
    FOREIGN KEY (ApplicantID)
        REFERENCES Applicants(ApplicantID),
    FOREIGN KEY (ApplicationID)
        REFERENCES Applications(ApplicationID)
        ON DELETE CASCADE
);
grant select on Creates to public;

CREATE TABLE InterviewsMakes (
    InterviewID         VARCHAR(100),
    TimeOfInterview     TIMESTAMP,
    ApplicationID       VARCHAR(100)        NOT NULL,
    PRIMARY KEY (InterviewID),
    FOREIGN KEY (ApplicationID)
        REFERENCES Applications(ApplicationID)
);
grant select on InterviewsMakes to public;

CREATE TABLE ImmigrationOfficersWorksIn (
    ECID                VARCHAR(100),		
    OfficerID           VARCHAR(100),
    NameOfOfficer       VARCHAR(100)        NOT NULL,
    PRIMARY KEY (ECID, OfficerID),
    FOREIGN KEY (ECID) 
        REFERENCES EmbassyConsulates(ECID)
        ON DELETE CASCADE
);
grant select on ImmigrationOfficersWorksIn to public;

CREATE TABLE InterviewedBy(
    InterviewID         VARCHAR(100),
    ECID                VARCHAR(100),
    OfficerID           VARCHAR(100),
    PRIMARY KEY (InterviewID, ECID, OfficerID),
    FOREIGN KEY (InterviewID)
        REFERENCES InterviewsMakes,
    FOREIGN KEY (ECID, OfficerID)
        REFERENCES ImmigrationOfficersWorksIn(ECID, OfficerID)
);
grant select on InterviewedBy to public;

INSERT into ApprovedInstitutions VALUES ('TXE2523', 'University of British Columbia', 'University/College');

INSERT into ApprovedInstitutions VALUES ('EFW0654', 'Microsoft', 'Company');

INSERT into EmbassyConsulates VALUES ('UK001', 'Canada House, Trafalgar Sq, London SW1Y 5BJ', 'United Kingdom');

INSERT into EmbassyConsulates VALUES ('US001', '466 Lexington Ave 20th floor, New York, NY 10017', 'United States');