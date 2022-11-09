-- drop table AddressCountry;
-- drop table ApprovedInstitutions;
-- drop table EmbassyConsulates;
-- drop table Applications;
-- drop table VisaFromIssue;
-- drop table AsylumRefugeeVisa;
-- drop table WorkVisaSponseredBy;
-- drop table StudentVisaVerifiedBy;
-- drop table InOut;
-- drop table TravelHistoryRecordsTravelsBy;
-- drop table Applicants;
-- drop table Applications;
-- drop table IssueDateExpirationDateStatus;
-- drop table Holds;
-- drop table ApplicantIDVisaIDStatus;
-- drop table Creates;
-- drop table InterviewsMakes;
-- drop table ImmigrationOfficersWorksIn;
-- drop table InterviewedBy;

CREATE TABLE AddressCountry (
    Address     VARCHAR(100)        PRIMARY KEY,
    Country     VARCHAR(100)        NOT NULL                 
);
-- grant select on AddressCountry to public;

-- CREATE TABLE ApprovedInstitutions (
--     InstitutionID          VARCHAR(100),		
--     InstitutionName        VARCHAR(100),
--     Category               VARCHAR(100), 
--     PRIMARY KEY (InstitutionID)
-- );
-- -- grant select on ApprovedInstitutions to public;

-- CREATE TABLE EmbassyConsulates (
--     ECID        VARCHAR(100)        PRIMARY KEY,
--     Address     VARCHAR(100)        UNIQUE,
--     FOREIGN KEY (Address)
--         REFERENCES AddressCountry
-- );
-- -- grant select on EmbassyConsulates to public;

-- CREATE TABLE Applications (
--     ApplicationID      VARCHAR(100)        PRIMARY KEY,
--     Status             NUMBER(1)           NOT NULL
-- );
-- -- grant select Applications to public;

-- CREATE TABLE VisaFromIssue (
--     VisaID          VARCHAR(100)        PRIMARY KEY,
--     VisaType        VARCHAR(100),
--     ApplicationID   VARCHAR(100)        NOT NULL,
--     ECID            VARCHAR(100)        NOT NULL,
--     IssueDate DATE,
--     FOREIGN KEY (ApplicationID)
--         REFERENCES Applications
--         ON DELETE CASCADE,
--     FOREIGN KEY (ECID)
--         REFERENCES EmbassyConsulates
--         ON DELETE CASCADE
-- );
-- -- grant select VisaFromIssue to public;

-- CREATE TABLE AsylumRefugeeVisa (
--     VisaID      VARCHAR(100)        PRIMARY KEY,
--     Reason      VARCHAR(100)        NOT NULL,
--     FOREIGN KEY (VisaID) REFERENCES VisaFromIssue
--         ON DELETE CASCADE
-- );
-- -- grant select AsylumRefugeeVisa to public;

-- CREATE TABLE WorkVisaSponseredBy (
--     VisaID          VARCHAR(100)        PRIMARY KEY,
--     WorkType        VARCHAR(100)        NOT NULL,
--     InstitutionID   VARCHAR(100)        NOT NULL,    
--     FOREIGN KEY (VisaID)
--         REFERENCES VisaFromIssue
--         ON DELETE CASCADE, 
--     FOREIGN KEY (InstitutionID)
--         REFERENCES ApprovedInstitutions
-- );
-- -- grant select WorkVisaSponseredBy to public;

-- CREATE TABLE StudentVisaVerifiedBy (
--     VisaID          VARCHAR(100)		PRIMARY KEY,
--     StudyLevel      VARCHAR(100)        NOT NULL,
--     InstitutionID   VARCHAR(100)        NOT NULL,
--     FOREIGN KEY (VisaID)
--         REFERENCES VisaFromIssue,
--     FOREIGN KEY (InstitutionID) 
--         REFERENCES ApprovedInstitutions
-- );
-- -- grant select StudentVisaVerifiedBy to public;

-- CREATE TABLE InOut (
--     Destination     VARCHAR(100),
--     Departure       VARCHAR(100),
--     InOut          NUMBER(1)               NOT NULL,
--     PRIMARY KEY (Destination, Departure)
-- );

-- CREATE TABLE TravelHistoryRecordsTravelsBy (
--     RecordID        VARCHAR(100)        PRIMARY KEY,
--     TimeOfTravel    TIMESTAMP           NOT NULL,
--     DateOfTravel    DATE                NOT NULL,
--     Destination     VARCHAR(100)        NOT NULL,
--     Departure       VARCHAR(100)        NOT NULL,
--     VisaID          VARCHAR(100)        NOT NULL,
--     FOREIGN KEY (Destination, Departure)
--         REFERENCES InOut(Destination, Departure)
-- );
-- -- grant select TravelHistoryRecordsTravelsBy to public;

-- CREATE TABLE Applicants (
--     ApplicantID         VARCHAR(100)        PRIMARY KEY,
--     Name                VARCHAR(100)        NOT NULL,
--     Nationality         VARCHAR(100)        NOT NULL,
--     DateOfBirth         DATE                NOT NULL,
-- );
-- -- grant select Applicants to public;

-- CREATE TABLE Applications (
--      ApplicationID      VARCHAR(100)        PRIMARY KEY,
--      Status             NUMBER(1)           NOT NULL
-- );
-- -- grant select Applications to public;

-- CREATE TABLE IssueDateExpirationDateStatus (
--     IssueDate               DATE,
--     ExpirationDate          DATE,
--     Status                  NUMBER(1)           NOT NULL,
--     PRIMARY KEY (IssueDate, ExpirationDate) 
-- );
-- -- grant select IssueDateExpirationDateStatus to public;

-- CREATE TABLE Holds (
--     ApplicantID         VARCHAR(100),
--     VisaID              VARCHAR(100),
--     IssueDate           DATE                NOT NULL,
--     ExpirationDate      DATE                NOT NULL,
--     PRIMARY KEY (ApplicantID, VisaID),
--     FOREIGN KEY (IssueDate, ExpirationDate)
--     REFERENCES IssueDateExpirationDateStatus(IssueDate, ExpirationDate)
-- );
-- -- grant select Holds to public;

-- CREATE TABLE ApplicantIDVisaIDStatus (
--     ApplicantID             VARCHAR(100),
--     VisaID                  VARCHAR(100),
--     Status                  NUMBER(1)               NOT NULL,
--     PRIMARY KEY (ApplicantID, VisaID),    
--     FOREIGN KEY (ApplicantID, VisaID)
--         REFERENCES Holds(ApplicantID, VisaID)
--         ON DELETE CASCADE       
-- );
-- -- grant select ApplicantIDVisaIDStatus to public;

-- CREATE TABLE Creates (
--     ApplicantID             VARCHAR(100),
--     ApplicationID           VARCHAR(100),
--     CreateDate              DATE                    NOT NULL,
--     PRIMARY KEY (ApplicantID, ApplicationID),
--     FOREIGN KEY (ApplicantID)
--         REFERENCES Applicants(ApplicantID),
--     FOREIGN KEY (ApplicationID)
--         REFERENCES Applications(ApplicationID)
--         ON DELETE CASCADE
-- );
-- -- grant select Creates to public;

-- CREATE TABLE InterviewsMakes (
--     InterviewID         VARCHAR(100),		
--     DateOfInterview     DATE,
--     TimeOfInterview     TIMESTAMP,
--     ApplicationID       VARCHAR(100)        NOT NULL,
--     PRIMARY KEY (InterviewID),
--     FOREIGN KEY (ApplicationID)
--         REFERENCES Applications(ApplicationID)
-- );
-- -- grant select InterviewsMakes to public;

-- CREATE TABLE ImmigrationOfficersWorksIn (
--     ECID            VARCHAR(100),		
--     OfficerID       VARCHAR(100),
--     Name            VARCHAR(100)        NOT NULL,
--     PRIMARY KEY (ECID, OfficerID),
--     FOREIGN KEY (ECID) 
--         REFERENCES EmbassyConsulates(ECID)
--         ON DELETE CASCADE
-- );
-- -- grant select ImmigrationOfficersWorksIn to public;

-- CREATE TABLE InterviewedBy(
--     InterviewID         VARCHAR(100),
--     ECID                VARCHAR(100),
--     OfficerID           VARCHAR(100),
--     PRIMARY KEY (InterviewID, ECID, OfficerID),
--     FOREIGN KEY (InterviewID)
--         REFERENCES InterviewsMakes,
--     FOREIGN KEY (ECID, OfficerID)
--         REFERENCES ImmigrationOfficersWorksIn(ECID, OfficerID)
-- );
-- -- grant select InterviewedBy to public;
